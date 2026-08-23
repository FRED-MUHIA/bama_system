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
            return 'Gmail needs an App Password. Open your Gmail account security settings, turn on 2-Step Verification, create an App Password, then paste that App Password here. Do not use your normal Gmail password.';
        }

        if (str_contains($lower, 'yahoo') && (str_contains($lower, 'authenticate') || str_contains($lower, 'password') || str_contains($lower, '535'))) {
            return 'Yahoo needs an App Password. Open your Yahoo account security settings, create a third-party App Password, then paste that App Password here. Do not use your normal Yahoo password.';
        }

        if (str_contains($lower, '535') || str_contains($lower, 'failed to authenticate')) {
            return 'The email username or password is not accepted. Use the full email address as username. For Gmail or Yahoo, paste an App Password, not the normal email password.';
        }

        if (str_contains($lower, 'connection could not be established') || str_contains($lower, 'connection refused') || str_contains($lower, 'timed out')) {
            return 'The email server could not be reached. Check the mail host, port and security option, or ask the email provider for the correct outgoing mail settings.';
        }

        return $message;
    }
}
