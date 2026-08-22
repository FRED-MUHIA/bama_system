<?php

namespace App\Services;

use App\Models\MailSetting;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

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

        $query = MailSetting::query()->where('enabled', true);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $setting = $query->first();

        if ($setting) {
            $setting->apply();
        }

        return $setting;
    }

    public function sendRaw(string $to, string $subject, string $body, ?callable $configure = null, ?int $businessId = null): void
    {
        if (blank($to)) {
            throw new InvalidArgumentException('Recipient email address is required.');
        }

        $this->apply($businessId);

        Mail::raw($body, function ($mail) use ($to, $subject, $configure) {
            $mail->to($to)->subject($subject);

            if ($configure) {
                $configure($mail);
            }
        });
    }
}
