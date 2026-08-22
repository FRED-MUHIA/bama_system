<?php

namespace Modules\Retail\Services;

use Modules\Retail\Models\ProductVerification;
use Modules\Retail\Models\ScanEvent;

class FraudDetectionService
{
    public function assess(ScanEvent $event, ?ProductVerification $verification = null): array
    {
        $recentInvalid = ScanEvent::where('identifier_value', $event->identifier_value)
            ->where('result', 'Failure')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        $fraud = (bool) ($verification?->fraud_suspected) || $recentInvalid >= 3;

        return [
            'fraud_suspected' => $fraud,
            'risk_level' => $fraud ? 'High' : ($recentInvalid > 0 ? 'Medium' : 'Low'),
            'recent_invalid_scans' => $recentInvalid,
        ];
    }
}
