<?php

namespace Modules\Retail\Services;

use App\Services\IamService;
use Modules\Retail\Models\ScanAuditLog;
use Modules\Retail\Models\ScanEvent;

class ScanAuditService
{
    public function log(ScanEvent $event, string $auditEvent, string $result, array $payload = []): ScanAuditLog
    {
        $log = ScanAuditLog::create([
            'scan_event_id' => $event->id,
            'scan_device_id' => $event->scan_device_id,
            'product_id' => $event->product_id,
            'user_id' => auth()->id(),
            'branch_id' => $event->branch_id,
            'event' => $auditEvent,
            'result' => $result,
            'device_code' => $event->device?->device_code,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'payload' => $payload,
        ]);

        app(IamService::class)->audit('retail.scanning.'.$auditEvent, $event);

        return $log;
    }
}
