<?php

namespace App\Support;

use App\Models\PlatformPaymentSetting;

class BamaBilling
{
    public static function enabled(): bool
    {
        return SchemaCache::hasTable('platform_payment_settings')
            && PlatformPaymentSetting::query()->where('is_enabled', true)->exists();
    }

    public static function visible(array $billingState = []): bool
    {
        return self::enabled()
            || in_array($billingState['state'] ?? 'active', ['renewal_due', 'grace', 'locked'], true);
    }
}
