<?php

namespace App\Console\Commands;

use App\Models\BusinessTemplate;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\DocumentTemplate;
use App\Models\EmailLog;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\MailSetting;
use App\Models\PlatformPaymentSetting;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Modules\Retail\Models\RetailEcommerceIntegration;

class EncryptSensitiveDataCommand extends Command
{
    protected $signature = 'security:encrypt-sensitive-data
        {--dry-run : Count plaintext values without changing data}
        {--chunk=200 : Number of records to process per batch}';

    protected $description = 'Encrypt existing plaintext sensitive content and secrets using the app key.';

    private const ENCRYPTED_FIELDS = [
        Client::class => ['address' => 'string', 'billing_address' => 'string', 'kra_pin' => 'string', 'notes' => 'string'],
        Supplier::class => ['kra_pin' => 'string', 'address' => 'string'],
        CompanySetting::class => ['address' => 'string', 'default_terms' => 'string'],
        Site::class => ['address' => 'string', 'notes' => 'string'],
        Project::class => ['scope' => 'string', 'notes' => 'string'],
        Letter::class => ['content' => 'string', 'recipient' => 'string'],
        LetterTemplate::class => ['content' => 'string'],
        BusinessTemplate::class => ['content' => 'string'],
        DocumentTemplate::class => ['content' => 'string'],
        ProjectDocument::class => ['content' => 'string'],
        EmailLog::class => ['message' => 'string', 'error' => 'string'],
        MailSetting::class => ['username' => 'string', 'password' => 'string'],
        PlatformPaymentSetting::class => ['secret_key' => 'string', 'config' => 'array'],
        RetailEcommerceIntegration::class => ['settings' => 'array'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $totals = ['records' => 0, 'fields' => 0, 'skipped' => 0];

        foreach (self::ENCRYPTED_FIELDS as $modelClass => $fields) {
            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                $this->warn("Skipping {$modelClass}: table {$table} does not exist.");
                continue;
            }

            $fields = array_filter(
                $fields,
                fn (string $field) => Schema::hasColumn($table, $field),
                ARRAY_FILTER_USE_KEY
            );

            if ($fields === []) {
                $this->warn("Skipping {$modelClass}: no configured sensitive columns exist.");
                continue;
            }

            $stats = $this->encryptModel($modelClass, $fields, $chunkSize, $dryRun);
            $totals['records'] += $stats['records'];
            $totals['fields'] += $stats['fields'];
            $totals['skipped'] += $stats['skipped'];

            $label = class_basename($modelClass);
            $verb = $dryRun ? 'would protect' : 'protected';
            $this->line("{$label}: {$verb} {$stats['fields']} field value(s) across {$stats['records']} record(s); skipped {$stats['skipped']} value(s).");
        }

        $prefix = $dryRun ? 'Dry run complete:' : 'Encryption complete:';
        $this->components->info("{$prefix} {$totals['fields']} field value(s) across {$totals['records']} record(s). Skipped {$totals['skipped']} value(s).");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $fields
     * @return array{records: int, fields: int, skipped: int}
     */
    private function encryptModel(string $modelClass, array $fields, int $chunkSize, bool $dryRun): array
    {
        $stats = ['records' => 0, 'fields' => 0, 'skipped' => 0];

        $modelClass::withoutGlobalScopes()
            ->where(function ($query) use ($fields) {
                foreach (array_keys($fields) as $field) {
                    $query->orWhereNotNull($field);
                }
            })
            ->chunkById($chunkSize, function ($records) use ($fields, $dryRun, &$stats) {
                foreach ($records as $record) {
                    $updated = false;

                    foreach ($fields as $field => $type) {
                        if (! $this->shouldEncryptField($record, $field)) {
                            continue;
                        }

                        $value = $record->{$field};

                        if ($type === 'array' && ! is_array($value)) {
                            $stats['skipped']++;
                            continue;
                        }

                        if ($dryRun) {
                            $updated = true;
                            $stats['fields']++;
                            continue;
                        }

                        $record->{$field} = $value;
                        $updated = true;
                        $stats['fields']++;
                    }

                    if ($updated) {
                        $stats['records']++;

                        if (! $dryRun) {
                            $record->saveQuietly();
                        }
                    }
                }
            });

        return $stats;
    }

    private function shouldEncryptField(Model $record, string $field): bool
    {
        $raw = $record->getRawOriginal($field);

        if ($raw === null || $raw === '') {
            return false;
        }

        try {
            Crypt::decryptString((string) $raw);

            return false;
        } catch (DecryptException) {
            try {
                Crypt::decrypt((string) $raw);

                return false;
            } catch (DecryptException) {
                return true;
            }
        }
    }
}
