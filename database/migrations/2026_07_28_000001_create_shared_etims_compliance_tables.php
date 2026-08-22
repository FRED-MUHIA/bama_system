<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('etims_submissions')) {
            Schema::create('etims_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->nullableMorphs('source');
                $table->string('industry')->nullable()->index();
                $table->string('document_type')->default('Fiscal Invoice');
                $table->string('fiscal_invoice_number')->nullable();
                $table->string('fiscal_receipt_number')->nullable();
                $table->text('qr_code')->nullable();
                $table->json('payload')->nullable();
                $table->json('validation_result')->nullable();
                $table->string('status')->default('Pending');
                $table->unsignedInteger('attempt_count')->default(0);
                $table->dateTime('next_retry_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('validated_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status']);
                $table->index(['business_id', 'document_type']);
            });
        }

        if (! Schema::hasTable('etims_audit_logs')) {
            Schema::create('etims_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('etims_submission_id')->nullable()->constrained('etims_submissions')->nullOnDelete();
                $table->string('event');
                $table->string('status')->nullable();
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'event']);
            });
        }

        $this->registerSharedEtimsModule();
    }

    public function down(): void
    {
        Schema::dropIfExists('etims_audit_logs');
        Schema::dropIfExists('etims_submissions');
    }

    private function registerSharedEtimsModule(): void
    {
        $now = now();
        $permissions = ['etims.view', 'etims.manage', 'etims.reports', 'etims.retry'];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    [
                        'module' => 'etims',
                        'description' => Str::headline(str_replace(['etims.', '.', '-'], ['ETIMS ', ' ', ' '], $permission)),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->updateOrInsert(
                ['slug' => 'etims-compliance'],
                [
                    'name' => 'ETIMS Compliance',
                    'namespace' => 'Shared\\Compliance\\Etims',
                    'type' => 'shared',
                    'industry' => null,
                    'icon' => 'bi-receipt-cutoff',
                    'route' => null,
                    'permissions' => json_encode($permissions),
                    'menu' => json_encode(['label' => 'ETIMS Compliance', 'group' => 'Compliance', 'icon' => 'bi-receipt-cutoff', 'route' => null]),
                    'widgets' => json_encode(['etims-submitted-invoices', 'etims-pending-invoices', 'etims-failed-submissions', 'etims-compliance-rate']),
                    'is_core' => true,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['etims-submitted-invoices', 'ETIMS Submitted Invoices', 'etims.view'],
                ['etims-pending-invoices', 'ETIMS Pending Invoices', 'etims.view'],
                ['etims-failed-submissions', 'ETIMS Failed Submissions', 'etims.reports'],
                ['etims-compliance-rate', 'ETIMS Compliance Rate', 'etims.reports'],
            ] as [$slug, $name, $permission]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'etims-compliance',
                        'industry' => null,
                        'component' => 'dashboard.widgets.metric-card',
                        'permission' => $permission,
                        'settings_schema' => json_encode(['supports_period_filters' => true, 'supports_industry_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
};
