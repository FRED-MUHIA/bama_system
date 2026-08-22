<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Retail\Models\ProductBatch;
use Modules\Retail\Models\CameraScanEvent;
use Modules\Retail\Models\RetailProductProfile;
use Modules\Retail\Models\ScanEvent;
use Modules\Retail\Models\SelfCheckoutTransaction;
use Modules\Retail\Services\BatchTrackingService;
use Modules\Retail\Services\SmartProductScanningService;
use Tests\TestCase;

class SmartProductScanningTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::where('slug', 'bama')->firstOrFail();
        $this->user = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($this->user)->withSession(['active_business_id' => $this->business->id]);
    }

    public function test_scanner_identifies_product_and_returns_pos_cart_item(): void
    {
        $product = $this->product('SKU-SCAN', 250, 5);
        RetailProductProfile::create(['product_id' => $product->id, 'barcode' => 'BAR-100', 'product_type' => 'Physical Product', 'status' => 'Active']);

        $result = app(SmartProductScanningService::class)->scan([
            'raw_value' => 'BAR-100',
            'input_type' => 'POS Scanner Device',
            'device_code' => 'REG-01',
            'quantity' => 2,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame($product->id, $result['product']['id']);
        $this->assertSame($product->id, $result['cart_item']['product_id']);
        $this->assertDatabaseHas('scan_events', ['identifier_value' => 'BAR-100', 'result' => 'Success']);
        $this->assertDatabaseHas('scan_audit_logs', ['event' => 'product-scanned', 'result' => 'Success']);
    }

    public function test_expired_batch_is_blocked_before_sale(): void
    {
        $product = $this->product('SKU-EXP', 100, 5);
        app(BatchTrackingService::class)->createOrUpdate($product, [
            'batch_number' => 'EXP-1',
            'expiry_date' => now()->subDay()->toDateString(),
            'quantity' => 5,
        ]);

        $result = app(SmartProductScanningService::class)->scan([
            'raw_value' => 'sku=SKU-EXP|batch_number=EXP-1',
            'quantity' => 1,
            'update_inventory' => true,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', strtolower($result['message']));
        $this->assertSame('5.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('scan_audit_logs', ['event' => 'expired-product', 'result' => 'Failure']);
    }

    public function test_successful_sale_scan_updates_inventory_and_batch_traceability(): void
    {
        $product = $this->product('SKU-SALE', 100, 10);
        $batch = app(BatchTrackingService::class)->createOrUpdate($product, [
            'batch_number' => 'B-1',
            'expiry_date' => now()->addMonth()->toDateString(),
            'quantity' => 10,
        ]);

        $result = app(SmartProductScanningService::class)->scan([
            'raw_value' => 'sku=SKU-SALE|batch_number=B-1',
            'quantity' => 3,
            'update_inventory' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('7.000', $product->fresh()->stock_quantity);
        $this->assertSame('7.000', $batch->fresh()->quantity);
        $this->assertSame(3.0, (float) ScanEvent::first()->sold_quantity);
        $this->assertDatabaseHas('product_batch_movements', ['product_batch_id' => $batch->id, 'type' => 'Sale']);
    }

    public function test_self_checkout_builds_transaction_from_valid_scans(): void
    {
        $this->product('SKU-SCO-1', 50, 4);
        $this->product('SKU-SCO-2', 75, 4);

        $checkout = app(SmartProductScanningService::class)->selfCheckout([
            'payment_method' => 'Mobile Payment',
            'scans' => [
                ['raw_value' => 'SKU-SCO-1', 'quantity' => 1],
                ['raw_value' => 'SKU-SCO-2', 'quantity' => 2],
            ],
        ]);

        $this->assertInstanceOf(SelfCheckoutTransaction::class, $checkout);
        $this->assertSame('Ready For Payment', $checkout->status);
        $this->assertSame('200.00', $checkout->total);
        $this->assertCount(2, $checkout->cart_payload);
    }

    public function test_camera_upload_decodes_image_text_and_relates_product(): void
    {
        Storage::fake('public');
        $product = $this->product('SKU-CAM', 185, 9);
        RetailProductProfile::create(['product_id' => $product->id, 'barcode' => '9876543210123', 'product_type' => 'Physical Product', 'status' => 'Active']);

        $image = UploadedFile::fake()->createWithContent(
            'camera-scan.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>barcode: 9876543210123</text></svg>'
        );

        $result = app(SmartProductScanningService::class)->camera([
            'barcode_image' => $image,
            'camera_type' => 'Mobile Camera',
            'quantity' => 1,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame($product->id, $result['product']['id']);
        $this->assertSame('9876543210123', $result['scan_event']['identifier_value']);
        $this->assertDatabaseHas('camera_scan_events', ['detection_result' => 'Identified', 'camera_type' => 'Mobile Camera']);
        $this->assertNotNull(CameraScanEvent::first()->image_path);
    }

    private function product(string $sku, float $price, float $stock): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Scan Test']);

        return Product::create([
            'product_category_id' => $category->id,
            'name' => 'Scan Product '.$sku,
            'sku' => $sku,
            'price' => $price,
            'cost_price' => 20,
            'stock_quantity' => $stock,
            'reorder_level' => 1,
            'stock_unit' => 'pcs',
            'is_active' => true,
        ]);
    }
}
