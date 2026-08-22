<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailLoyaltyAccount;
use Modules\Retail\Services\RetailGiftCardService;
use Modules\Retail\Services\RetailInventoryService;
use Modules\Retail\Services\RetailLoyaltyService;
use Modules\Retail\Services\RetailOrderService;
use Modules\Retail\Services\RetailPosService;
use Shared\Compliance\Etims\Contracts\EtimsComplianceServiceContract;
use Shared\Compliance\Etims\Models\EtimsSubmission;
use Tests\TestCase;

class RetailManagementTest extends TestCase
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

    public function test_retail_inventory_extends_shared_product_stock(): void
    {
        $product = $this->product('SKU-INV');

        $balance = app(RetailInventoryService::class)->receive($product, 12, ['reference' => 'GRN-1']);
        app(RetailInventoryService::class)->reserve($product->fresh(), 3);

        $this->assertSame('12.000', $product->fresh()->stock_quantity);
        $this->assertSame('9.000', $balance->fresh()->available_stock);
        $this->assertSame('3.000', $balance->fresh()->reserved_stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'Received']);
    }

    public function test_loyalty_and_gift_card_balances_are_tracked(): void
    {
        $client = Client::create(['name' => 'Retail Customer', 'email' => 'retail@example.test']);

        $account = app(RetailLoyaltyService::class)->earn($client, 2500);
        app(RetailLoyaltyService::class)->redeem($client, 500);
        $card = app(RetailGiftCardService::class)->issue(1000, $client);
        app(RetailGiftCardService::class)->redeem($card, 250);

        $this->assertSame(2000, $account->fresh()->points_balance);
        $this->assertSame('750.00', $card->fresh()->balance);
        $this->assertCount(2, $card->fresh()->transactions);
    }

    public function test_retail_order_service_calculates_totals(): void
    {
        $product = $this->product('SKU-ORDER', 100);
        $client = Client::create(['name' => 'Online Customer']);

        $order = app(RetailOrderService::class)->create([
            'client_id' => $client->id,
            'channel' => 'Online Store',
            'status' => 'Confirmed',
            'items' => [[
                'product_id' => $product->id,
                'title' => $product->name,
                'quantity' => 2,
                'unit_price' => 100,
                'discount' => 10,
                'tax_rate' => 10,
            ]],
        ]);

        $this->assertSame('Confirmed', $order->status);
        $this->assertSame('209.00', $order->total);
        $this->assertCount(1, $order->items);
    }

    public function test_retail_pos_sale_creates_shared_etims_fiscal_submission(): void
    {
        $product = $this->product('SKU-ETIMS', 116);
        $product->update(['stock_quantity' => 3]);
        $cash = \App\Models\PaymentMethod::create(['name' => 'Cash', 'type' => 'Cash', 'is_active' => true]);

        $order = app(RetailPosService::class)->createSale([
            'sale_type' => 'Sale',
            'channel' => 'Store',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
                'discount' => 0,
                'tax_rate' => 16,
            ]],
            'payments' => [[
                'payment_method_id' => $cash->id,
                'method_type' => 'Cash',
                'amount' => 116,
                'reference' => 'CASH-ETIMS',
            ]],
        ]);

        $submission = $order->etimsSubmissions()->firstOrFail();

        $this->assertSame(EtimsSubmission::STATUS_VALIDATED, $submission->status);
        $this->assertSame('retail', $submission->industry);
        $this->assertSame('Fiscal Invoice', $submission->document_type);
        $this->assertNotEmpty($submission->fiscal_invoice_number);
        $this->assertNotEmpty($submission->fiscal_receipt_number);
        $this->assertStringStartsWith('ETIMS|', $submission->qr_code);
        $this->assertDatabaseHas('etims_audit_logs', ['etims_submission_id' => $submission->id, 'event' => 'validated']);
    }

    public function test_shared_etims_offline_queue_can_retry(): void
    {
        $product = $this->product('SKU-ETIMS-OFF', 100);
        $order = \App\Models\PosOrder::create([
            'order_number' => 'POS-OFF-1',
            'tracking_key' => 'POSOFF1',
            'order_date' => now(),
            'customer_name' => 'Offline Customer',
            'status' => 'paid',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 16,
            'total' => 116,
            'amount_paid' => 116,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'title' => $product->name,
            'description' => $product->name,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 16,
            'line_total' => 116,
        ]);

        $submission = app(EtimsComplianceServiceContract::class)->submitSale($order, ['industry' => 'retail', 'offline' => true]);

        $this->assertSame(EtimsSubmission::STATUS_OFFLINE, $submission->status);

        $submission->update(['next_retry_at' => now()->subMinute()]);
        app(EtimsComplianceServiceContract::class)->retryPending();

        $this->assertSame(EtimsSubmission::STATUS_VALIDATED, $submission->fresh()->status);
    }

    public function test_retail_records_are_isolated_by_active_business(): void
    {
        $otherBusiness = Business::create([
            'tenant_id' => Tenant::first()?->id,
            'name' => 'Other Retailer',
            'slug' => 'other-retailer',
            'is_active' => true,
        ]);

        $visible = $this->product('SKU-A');
        RetailInventoryBalance::create(['product_id' => $visible->id, 'available_stock' => 5]);

        $hidden = Product::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Hidden Product',
            'sku' => 'SKU-B',
            'price' => 100,
            'stock_quantity' => 0,
            'stock_unit' => 'pcs',
            'is_active' => true,
        ]);
        RetailInventoryBalance::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'product_id' => $hidden->id,
            'available_stock' => 9,
        ]);

        $this->assertSame(1, RetailInventoryBalance::count());
        $this->assertSame($visible->id, RetailInventoryBalance::first()->product_id);

        $this->withSession(['active_business_id' => $otherBusiness->id]);
        $this->assertSame(1, RetailInventoryBalance::count());
        $this->assertSame($hidden->id, RetailInventoryBalance::first()->product_id);
    }

    private function product(string $sku, float $price = 50): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Retail Test']);

        return Product::create([
            'product_category_id' => $category->id,
            'name' => 'Retail Product '.$sku,
            'sku' => $sku,
            'price' => $price,
            'cost_price' => 25,
            'stock_quantity' => 0,
            'reorder_level' => 2,
            'stock_unit' => 'pcs',
            'is_active' => true,
        ]);
    }
}
