<?php

namespace App\Services;

use App\Models\MailSetting;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OutgoingMailService
{
    public function apply(?int $businessId = null): ?MailSetting
    {
        if (! Schema::hasTable('mail_settings')) {
            return null;
        }

        if ($businessId === null && auth()->check()) {
            $businessId = ActiveBusiness::id();
        }

        if (! $businessId) {
            return null;
        }

        $setting = MailSetting::withoutGlobalScopes()
            ->where('enabled', true)
            ->where('business_id', $businessId)
            ->first();

        if ($setting) {
            $setting->apply();
        }

        return $setting;
    }

    public function sendRaw(string $to, string $subject, string $body, ?callable $configure = null, ?int $businessId = null, bool $requireProfileSender = false): void
    {
        if (blank($to)) {
            throw new InvalidArgumentException('Recipient email address is required.');
        }

        $setting = $this->apply($businessId);

        if ($requireProfileSender && ! $setting) {
            throw new RuntimeException('Enable SMTP mail settings for this profile before emailing documents.');
        }

        Mail::raw($body, function ($mail) use ($to, $subject, $configure, $setting) {
            $mail->to($to)->subject($subject);

            if ($setting) {
                $mail->from($setting->from_address, $setting->from_name);
                $mail->replyTo($setting->from_address, $setting->from_name);
            }

            if ($configure) {
                $configure($mail);
            }
        });
    }

    public function userFacingError(Throwable $e): string
    {
        $message = $e->getMessage();
        $lower = strtolower($message);

        if (
            str_contains($lower, 'application-specific password')
            || str_contains($lower, 'invalidsecondfactor')
            || str_contains($lower, '534-5.7.9')
        ) {
            return 'Gmail rejected the normal account password. Turn on 2-Step Verification, create a Gmail App Password, then paste that app password in Business Email settings. Use smtp.gmail.com, port 465, SSL/TLS, and the full Gmail address as username.';
        }

        if (str_contains($lower, 'yahoo') && (str_contains($lower, 'authenticate') || str_contains($lower, 'password') || str_contains($lower, '535'))) {
            return 'Yahoo rejected the mailbox password. Create a Yahoo third-party app password, then paste that app password in Business Email settings. Use smtp.mail.yahoo.com, port 465, SSL/TLS, and the full Yahoo address as username.';
        }

        if (str_contains($lower, '535') || str_contains($lower, 'failed to authenticate')) {
            return 'The email provider rejected the username or password. Use the full mailbox address as username and, for Gmail or Yahoo, use an app password instead of the normal login password.';
        }

        if (str_contains($lower, 'connection could not be established') || str_contains($lower, 'connection refused') || str_contains($lower, 'timed out')) {
            return 'The SMTP server could not be reached. Check the host, port, security setting, and whether the hosting provider allows outgoing SMTP.';
        }

        return $message;
    }
}
