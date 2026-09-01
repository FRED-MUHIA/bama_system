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

        $mailData = $this->brandedMailData($subject, $body, $setting);

        Mail::send(['html' => 'emails.bama-system', 'text' => 'emails.bama-text'], $mailData, function ($mail) use ($to, $subject, $configure, $setting, $ccRecipients) {
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

    private function brandedMailData(string $subject, string $body, ?MailSetting $setting): array
    {
        $actionUrl = $this->firstUrlIn($body);

        return [
            'appName' => $setting?->from_name ?: config('mail.brand.name', 'Bama'),
            'subject' => $subject,
            'headline' => $subject,
            'body' => $body,
            'preheader' => str($body)->squish()->limit(140)->toString(),
            'actionUrl' => $actionUrl,
            'actionText' => 'Open secure link',
            'footerNote' => $actionUrl ? 'If the button does not work, copy and paste the link from this email into your browser.' : null,
        ];
    }

    private function firstUrlIn(string $body): ?string
    {
        if (! preg_match('/https?:\/\/[^\s<>"\']+/i', $body, $matches)) {
            return null;
        }

        return rtrim($matches[0], '.,);]');
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
