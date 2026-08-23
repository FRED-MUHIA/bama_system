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

    public function sendRaw(string $to, string $subject, string $body, ?callable $configure = null, ?int $businessId = null, bool $requireProfileSender = false, array|string|null $cc = null): void
    {
        if (blank($to)) {
            throw new InvalidArgumentException('Recipient email address is required.');
        }

        $ccRecipients = $this->normalizeEmailList($cc);
        $setting = $this->apply($businessId);

        if ($requireProfileSender && ! $setting) {
            throw new RuntimeException('Enable SMTP mail settings for this profile before emailing documents.');
        }

        if ($requireProfileSender && $setting) {
            $this->assertAllowedSenderDomain($setting->from_address);
        }

        Mail::raw($body, function ($mail) use ($to, $subject, $configure, $setting, $ccRecipients) {
            $mail->to($to)->subject($subject);

            if ($ccRecipients) {
                $mail->cc($ccRecipients);
            }

            if ($setting) {
                $mail->from(config('mail.from.address'), $setting->from_name);
                if (config('mail.from.address') !== $setting->from_address) {
                    $mail->replyTo($setting->from_address, $setting->from_name);
                }
            }

            if ($configure) {
                $configure($mail);
            }
        });
    }

    private function normalizeEmailList(array|string|null $emails): array
    {
        if (blank($emails)) {
            return [];
        }

        if (is_string($emails)) {
            $emails = preg_split('/[\s,;]+/', $emails, -1, PREG_SPLIT_NO_EMPTY);
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($email) => trim((string) $email),
            $emails
        ))));
    }

    private function assertAllowedSenderDomain(string $email): void
    {
        $requiredDomain = strtolower((string) config('mail.required_sender_domain'));

        if ($requiredDomain === '') {
            return;
        }

        $senderDomain = strtolower(str($email)->after('@')->toString());

        if ($senderDomain !== $requiredDomain) {
            throw new RuntimeException("This profile is configured to send from {$senderDomain}. Use a {$requiredDomain} mailbox before emailing documents.");
        }
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
            return 'Gmail rejected this mailbox login. Connect this profile with its own Gmail App Password, or switch off corporate integration and use server mail.';
        }

        if (str_contains($lower, 'yahoo') && (str_contains($lower, 'authenticate') || str_contains($lower, 'password') || str_contains($lower, '535'))) {
            return 'Yahoo rejected this mailbox login. Connect this profile with its own Yahoo App Password, or switch off corporate integration and use server mail.';
        }

        if (str_contains($lower, '535') || str_contains($lower, 'failed to authenticate')) {
            return 'This mailbox login was rejected. Check the email address and app password, or switch off corporate integration and use server mail.';
        }

        if (str_contains($lower, 'sendmail') || str_contains($lower, 'proc_open') || str_contains($lower, 'no such file') || str_contains($lower, 'not found')) {
            return 'The server mail service is not available. Ask the system owner to enable server mail, or connect this profile with its own corporate email server.';
        }

        if (str_contains($lower, 'connection could not be established') || str_contains($lower, 'connection refused') || str_contains($lower, 'timed out')) {
            return 'The email server could not be reached. Check the mail host, port and security option, or ask the email provider for the correct outgoing mail settings.';
        }

        return $message;
    }
}
