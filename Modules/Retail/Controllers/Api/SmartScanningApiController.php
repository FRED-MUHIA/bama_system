<?php

namespace Modules\Retail\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Retail\Models\ScanEvent;
use Modules\Retail\Services\SmartProductScanningService;
use Modules\Retail\Services\SmartScanningAnalyticsService;

class SmartScanningApiController extends Controller
{
    public function product(Request $request, SmartProductScanningService $scanner)
    {
        return response()->json(['data' => $scanner->scan($this->payload($request), $request->boolean('update_inventory'))]);
    }

    public function verify(Request $request, SmartProductScanningService $scanner)
    {
        return response()->json(['data' => $scanner->verify($this->payload($request))]);
    }

    public function camera(Request $request, SmartProductScanningService $scanner)
    {
        return response()->json(['data' => $scanner->camera($this->payload($request) + $request->only(['camera_type', 'image_path', 'confidence']))]);
    }

    public function selfCheckout(Request $request, SmartProductScanningService $scanner)
    {
        $data = $request->validate([
            'scan_device_id' => ['nullable', 'exists:scan_devices,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'string', 'max:100'],
            'receipt_channel' => ['nullable', 'string', 'max:100'],
            'scans' => ['required', 'array', 'min:1'],
            'scans.*.raw_value' => ['nullable', 'string'],
            'scans.*.scanner_input' => ['nullable', 'string'],
            'scans.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
        ]);

        return response()->json(['data' => $scanner->selfCheckout($data)], 201);
    }

    public function history(Request $request)
    {
        return response()->json([
            'data' => ScanEvent::with('product', 'device', 'verification')
                ->latest()
                ->paginate((int) $request->query('per_page', 25)),
        ]);
    }

    public function analytics(SmartScanningAnalyticsService $analytics)
    {
        return response()->json([
            'data' => [
                'metrics' => $analytics->metrics(),
                'trends' => $analytics->trends(),
                'top_products' => $analytics->topProducts(),
                'expiry_alerts' => $analytics->expiryAlerts(),
            ],
        ]);
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'raw_value' => ['nullable', 'string'],
            'scanner_input' => ['nullable', 'string'],
            'decoded_text' => ['nullable', 'string'],
            'manual_code' => ['nullable', 'string', 'max:255'],
            'barcode_image' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,bmp,gif,svg,txt,csv,json,xml'],
            'camera_image' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,bmp,gif,svg,txt,csv,json,xml'],
            'image' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,bmp,gif,svg,txt,csv,json,xml'],
            'input_type' => ['nullable', 'string', 'max:100'],
            'symbology' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'scan_device_id' => ['nullable', 'exists:scan_devices,id'],
            'device_code' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'register_number' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'retail_warehouse_id' => ['nullable', 'exists:retail_warehouses,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'pos_order_id' => ['nullable', 'exists:pos_orders,id'],
            'date_of_birth' => ['nullable', 'date'],
            'manager_override' => ['nullable', 'boolean'],
            'update_inventory' => ['nullable', 'boolean'],
            'camera_type' => ['nullable', 'string', 'max:100'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
