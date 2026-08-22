<?php

namespace Modules\Retail\Services;

use Illuminate\Support\Facades\DB;
use Modules\Retail\Models\ProductBatch;
use Modules\Retail\Models\ProductVerification;
use Modules\Retail\Models\ScanEvent;

class SmartScanningAnalyticsService
{
    public function metrics(): array
    {
        return [
            'Total Scans' => ScanEvent::count(),
            'Successful Scans' => ScanEvent::where('result', 'Success')->count(),
            'Failed Scans' => ScanEvent::where('result', 'Failure')->count(),
            'Expired Products Blocked' => ProductVerification::get()->filter(fn ($verification) => ($verification->checks['not_expired'] ?? true) === false)->count(),
            'Recalled Products Blocked' => ProductVerification::where('not_recalled', false)->count(),
            'Inventory Updated' => ScanEvent::where('sold_quantity', '>', 0)->count(),
            'Fraud Attempts' => ProductVerification::where('fraud_suspected', true)->count(),
        ];
    }

    public function trends()
    {
        return ScanEvent::select(DB::raw('DATE(scanned_at) as day'), DB::raw('COUNT(*) as scans'))
            ->groupBy('day')
            ->orderBy('day')
            ->limit(30)
            ->get();
    }

    public function topProducts()
    {
        return ScanEvent::join('products', 'products.id', '=', 'scan_events.product_id')
            ->select('products.name', DB::raw('COUNT(*) as scans'))
            ->groupBy('products.name')
            ->orderByDesc('scans')
            ->limit(10)
            ->get();
    }

    public function expiryAlerts()
    {
        return ProductBatch::with('product')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('expiry_date')
            ->limit(20)
            ->get();
    }
}
