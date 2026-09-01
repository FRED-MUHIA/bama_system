<?php

namespace App\Console\Commands;

use App\Services\OutgoingMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : Recipient email address} {--business= : Business ID for saved SMTP settings}';

    protected $description = 'Send a test email using the configured SMTP transport.';

    public function handle(OutgoingMailService $outgoingMail): int
    {
        $businessId = $this->option('business') ? (int) $this->option('business') : null;

        try {
            $outgoingMail->apply($businessId);

            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $scheme = config('mail.mailers.smtp.scheme') ?: ((int) $port === 465 ? 'smtps' : 'smtp');
            $from = config('mail.from.address');

            $this->components->info("Sending test email via {$scheme}://{$host}:{$port} from {$from}");

            Mail::raw('Your Bama SMTP settings are working.', function ($mail) {
                $mail->to($this->argument('email'))->subject('Bama SMTP test');
            });
        } catch (\Throwable $e) {
            report($e);
            $this->components->error('Test email failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Test email sent successfully.');

        return self::SUCCESS;
    }
}
