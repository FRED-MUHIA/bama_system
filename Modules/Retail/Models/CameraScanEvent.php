<?php

namespace Modules\Retail\Models;

class CameraScanEvent extends RetailModel
{
    protected $casts = [
        'detected_codes' => 'array',
        'detected_products' => 'array',
        'confidence' => 'decimal:2',
    ];

    public function scanEvent() { return $this->belongsTo(ScanEvent::class); }
    public function device() { return $this->belongsTo(ScanDevice::class, 'scan_device_id'); }
}
