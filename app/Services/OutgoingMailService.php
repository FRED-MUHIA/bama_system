<?php

namespace App\Services;

use App\Models\MailSetting;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

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
}
