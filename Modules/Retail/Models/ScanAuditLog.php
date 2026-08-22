<?php

namespace Modules\Retail\Models;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;

class ScanAuditLog extends RetailModel
{
    protected $casts = ['payload' => 'array'];

    public function scanEvent() { return $this->belongsTo(ScanEvent::class); }
    public function device() { return $this->belongsTo(ScanDevice::class, 'scan_device_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
}
