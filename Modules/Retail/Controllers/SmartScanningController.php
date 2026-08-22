<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\ProductBatch;
use Modules\Retail\Models\ScanDevice;
use Modules\Retail\Models\ScanEvent;
use Modules\Retail\Models\SelfCheckoutTransaction;
use Modules\Retail\Services\BatchTrackingService;
use Modules\Retail\Services\SmartProductScanningService;
use Modules\Retail\Services\SmartScanningAnalyticsService;

class SmartScanningController extends Controller
{
    public function index(SmartScanningAnalyticsService $analytics)
    {
        return view('retail.scanning.dashboard', [
            'metrics' => $analytics->metrics(),
            'trends' => $analytics->trends(),
            'topProducts' => $analytics->topProducts(),
            'expiryAlerts' => $analytics->expiryAlerts(),
            'recentScans' => ScanEvent::with('product', 'device', 'cashier')->latest()->limit(20)->get(),
            'devices' => ScanDevice::latest()->limit(20)->get(),
        ]);
    }

    public function devices()
    {
        return view('retail.scanning.devices', [
            'devices' => ScanDevice::with('branch', 'warehouse')->latest()->paginate(20),
        ]);
    }

    public function storeDevice(Request $request)
    {
        ScanDevice::create($request->validate([
            'device_code' => ['required', 'string', 'max:100', Rule::unique('scan_devices', 'device_code')->where('business_id', ActiveBusiness::id())],
            'name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', Rule::in(['POS Scanner', 'Mobile Camera', 'Self Checkout Camera', 'POS Camera', 'Scanner Device'])],
            'register_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:Active,Inactive,Maintenance'],
        ]));

        return back()->with('status', 'Scan device saved.');
    }

    public function batches()
    {
        return view('retail.scanning.batches', [
            'batches' => ProductBatch::with('product', 'supplier')->latest()->paginate(20),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeBatch(Request $request, BatchTrackingService $batches)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', ActiveBusiness::id())],
            'batch_number' => ['required', 'string', 'max:100'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'supplier_reference' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Active,Quarantined,Disabled'],
            'compliance_status' => ['required', 'in:Compliant,Non-Compliant,Pending Review'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        unset($data['product_id']);
        $batches->createOrUpdate($product, $data);

        return back()->with('status', 'Product batch saved.');
    }

    public function recall(Request $request, ProductBatch $batch, BatchTrackingService $batches)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $batches->recall($batch, $data['reason']);

        return back()->with('status', 'Batch recalled and quarantined.');
    }

    public function scan(Request $request, SmartProductScanningService $scanner)
    {
        $data = $this->scanPayload($request);
        $isCamera = $request->hasFile('barcode_image')
            || $request->hasFile('camera_image')
            || $request->hasFile('image')
            || in_array($data['input_type'] ?? null, ['Mobile Device Camera', 'Camera Feed', 'Barcode Image', 'QR Code Image'], true);

        $result = $isCamera
            ? $scanner->camera($data + $request->only(['camera_type', 'image_path', 'confidence']))
            : $scanner->scan($data, $request->boolean('update_inventory'));

        return back()->with('scanResult', $result);
    }

    private function scanPayload(Request $request): array
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
            'device_code' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'register_number' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_id' => ['nullable', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'date_of_birth' => ['nullable', 'date'],
            'manager_override' => ['nullable', 'boolean'],
            'update_inventory' => ['nullable', 'boolean'],
            'camera_type' => ['nullable', 'string', 'max:100'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
