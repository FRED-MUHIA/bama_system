<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Retail\Models\RetailCashDrawer;
use Modules\Retail\Models\RetailCycleCount;
use Modules\Retail\Models\RetailCustomerOffer;
use Modules\Retail\Models\RetailEcommerceIntegration;
use Modules\Retail\Models\RetailGiftCard;
use Modules\Retail\Models\RetailInventoryBalance;
use Modules\Retail\Models\RetailLoyaltyAccount;
use Modules\Retail\Models\RetailOrderFulfillment;
use Modules\Retail\Models\RetailPromotion;
use Modules\Retail\Models\RetailReplenishmentPlan;
use Modules\Retail\Models\RetailReturnAuthorization;
use Modules\Retail\Models\RetailSalesExtension;
use Modules\Retail\Models\RetailSupplierContract;
use Modules\Retail\Models\RetailTaxJurisdiction;
use Modules\Retail\Models\RetailWarehouse;
use Modules\Retail\Models\RetailWarehouseBin;
use Modules\Retail\Models\RetailWarehouseZone;
use Modules\Retail\Services\RetailGiftCardService;
use Tests\TestCase;

class RetailSectionWorkflowsTest extends TestCase
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

    public function test_retail_navigation_sections_render(): void
    {
        foreach ([
            'retail.dashboard',
            'retail.pos.index',
            'retail.products.index',
            'retail.inventory.index',
            'retail.warehousing.index',
            'retail.orders.index',
            'retail.customers.index',
            'retail.loyalty.index',
            'retail.promotions.index',
            'retail.gift-cards.index',
            'retail.returns.index',
            'retail.suppliers.index',
            'retail.branches.index',
            'retail.ecommerce.index',
            'retail.analytics.index',
            'retail.reports.index',
            'retail.settings.index',
            'retail.scanning.index',
        ] as $route) {
            $this->get(route($route))->assertStatus(200);
        }
    }

    public function test_branch_supplier_and_ecommerce_sections_have_working_actions(): void
    {
        $this->post(route('retail.branches.store'), [
            'name' => 'Downtown Store',
            'code' => 'DWN',
            'address' => 'Main street',
            'is_active' => 1,
        ])->assertSessionHas('status');

        $this->post(route('retail.suppliers.store'), [
            'name' => 'Retail Supplier',
            'email' => 'supplier@example.test',
            'supplier_code' => 'SUP-1',
            'lead_time_days' => 5,
            'rating' => 4.5,
        ])->assertSessionHas('status');

        $this->post(route('retail.ecommerce.store'), [
            'channel' => 'Online Store',
            'external_store_id' => 'SHOP-1',
            'status' => 'Draft',
            'product_sync' => 1,
            'inventory_sync' => 1,
            'order_sync' => 1,
            'customer_sync' => 1,
        ])->assertSessionHas('status');

        $integration = RetailEcommerceIntegration::firstOrFail();
        $this->post(route('retail.ecommerce.sync', $integration))->assertSessionHas('status');

        $this->assertDatabaseHas('branches', ['code' => 'DWN']);
        $this->assertDatabaseHas('suppliers', ['name' => 'Retail Supplier']);
        $this->assertNotNull($integration->fresh()->last_product_sync_at);
    }

    public function test_product_catalog_exposes_and_uses_shared_add_product_flow(): void
    {
        $this->get(route('retail.products.index'))
            ->assertStatus(200)
            ->assertSee('Add Product')
            ->assertSee('Upload CSV / Excel')
            ->assertSee(route('products.import'), false)
            ->assertSee(route('products.export', ['format' => 'csv']), false)
            ->assertSee(route('products.store'), false);

        $this->post(route('products.store'), [
            'name' => 'Catalog Added Product',
            'sku' => 'CAT-ADD-1',
            'price' => 125,
            'cost_price' => 75,
            'stock_quantity' => 6,
            'reorder_level' => 2,
            'stock_unit' => 'pcs',
            'is_active' => 1,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('products', ['sku' => 'CAT-ADD-1']);
        $this->assertDatabaseHas('stock_movements', ['type' => 'Add', 'reference' => 'Manual stock update']);
    }

    public function test_product_catalog_imports_and_exports_csv_and_excel_compatible_files(): void
    {
        $csv = implode("\n", [
            'name,sku,category,description,price,cost_price,stock_quantity,reorder_level,stock_unit,is_active',
            'Imported Shirt,IMP-1,Imported Category,First import,250,100,12,3,pcs,1',
        ]);

        $this->post(route('products.import'), [
            'product_file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('product_categories', ['name' => 'Imported Category']);
        $this->assertDatabaseHas('products', ['sku' => 'IMP-1', 'name' => 'Imported Shirt']);
        $this->assertDatabaseHas('stock_movements', ['type' => 'Add', 'reference' => 'Manual stock update']);

        $update = implode("\n", [
            'name,sku,category,description,price,cost_price,stock_quantity,reorder_level,stock_unit,is_active',
            'Imported Shirt Updated,IMP-1,Imported Category,Updated import,275,110,7,2,pcs,1',
        ]);

        $this->post(route('products.import'), [
            'product_file' => UploadedFile::fake()->createWithContent('products-update.csv', $update),
        ])->assertSessionHas('status');

        $product = Product::where('sku', 'IMP-1')->firstOrFail();
        $this->assertSame('Imported Shirt Updated', $product->name);
        $this->assertSame('7.000', $product->stock_quantity);

        $this->get(route('products.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get(route('products.export', ['format' => 'xls']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel');
    }

    public function test_order_management_can_add_customer_inline(): void
    {
        $this->get(route('retail.orders.index'))
            ->assertStatus(200)
            ->assertSee('Add Customer')
            ->assertSee(route('retail.orders.customers.store'), false);

        $this->post(route('retail.orders.customers.store'), [
            'name' => 'Inline Order Customer',
            'phone' => '555-1000',
            'email' => 'inline-order@example.test',
            'customer_segment' => 'VIP Customer',
            'address' => 'Retail lane',
        ])->assertSessionHas('status')
            ->assertSessionHas('selectedCustomerId');

        $this->assertDatabaseHas('clients', ['email' => 'inline-order@example.test']);
        $this->assertDatabaseHas('retail_customer_profiles', ['customer_segment' => 'VIP Customer']);
    }

    public function test_warehousing_inventory_returns_and_gift_cards_are_operational(): void
    {
        $branchA = Branch::create(['name' => 'A', 'code' => 'A', 'is_active' => true]);
        $branchB = Branch::create(['name' => 'B', 'code' => 'B', 'is_active' => true]);
        $product = $this->product('SKU-SECTIONS', 100, 10);

        $this->post(route('retail.warehousing.store'), [
            'branch_id' => $branchA->id,
            'code' => 'WH-A',
            'name' => 'Warehouse A',
            'warehouse_type' => 'Store Warehouse',
            'status' => 'Active',
        ])->assertSessionHas('status');

        $warehouse = RetailWarehouse::firstOrFail();
        $this->post(route('retail.warehousing.zones.store'), [
            'retail_warehouse_id' => $warehouse->id,
            'code' => 'Z-A',
            'name' => 'Zone A',
        ])->assertSessionHas('status');

        $zone = RetailWarehouseZone::firstOrFail();
        $this->post(route('retail.warehousing.bins.store'), [
            'retail_warehouse_id' => $warehouse->id,
            'retail_warehouse_zone_id' => $zone->id,
            'bin_code' => 'BIN-A',
            'capacity' => 100,
            'status' => 'Active',
        ])->assertSessionHas('status');

        RetailInventoryBalance::create(['product_id' => $product->id, 'branch_id' => $branchA->id, 'available_stock' => 10]);

        $this->post(route('retail.inventory.reserve'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'branch_id' => $branchA->id,
            'reference' => 'WEB-RES',
        ])->assertSessionHas('status');

        $this->post(route('retail.inventory.transfer'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'from_branch_id' => $branchA->id,
            'to_branch_id' => $branchB->id,
        ])->assertSessionHas('status');

        $client = Client::create(['name' => 'Return Customer']);
        $order = PosOrder::create([
            'client_id' => $client->id,
            'order_number' => 'POS-RET-1',
            'tracking_key' => 'TRACKRET1',
            'order_date' => now(),
            'customer_name' => $client->name,
            'status' => 'paid',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'amount_paid' => 100,
        ]);
        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'title' => $product->name,
            'description' => $product->name,
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => 100,
        ]);

        $this->post(route('retail.returns.store'), [
            'pos_order_id' => $order->id,
            'return_type' => 'Return',
            'reason' => 'Wrong size',
            'refund_method' => 'Store Credit',
            'items' => [[
                'pos_order_item_id' => $orderItem->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'condition' => 'Resellable',
                'refund_amount' => 100,
            ]],
        ])->assertSessionHas('status');

        $return = RetailReturnAuthorization::firstOrFail();
        $this->post(route('retail.returns.approve', $return))->assertSessionHas('status');

        $card = app(RetailGiftCardService::class)->issue(100, $client);
        $this->post(route('retail.gift-cards.recharge', $card), ['amount' => 50])->assertSessionHas('status');

        $this->assertDatabaseHas('retail_warehouse_bins', ['bin_code' => 'BIN-A']);
        $this->assertSame('3.000', RetailInventoryBalance::where('branch_id', $branchB->id)->first()->available_stock);
        $this->assertSame('Approved', $return->fresh()->approval_status);
        $this->assertSame('150.00', RetailGiftCard::first()->fresh()->balance);
    }

    public function test_retail_pos_exposes_full_cashier_workflow(): void
    {
        $product = $this->product('POS-SCAN-1', 100, 5);
        $product->retailProfile()->create(['barcode' => 'BAR-POS-1', 'product_type' => 'Physical Product', 'status' => 'Active']);

        $this->get(route('retail.pos.index', ['identifier' => 'BAR-POS-1']))
            ->assertStatus(200)
            ->assertSee('Product Search')
            ->assertSee('posProductSuggestions')
            ->assertSee('Scan Product')
            ->assertSee('Split Payments')
            ->assertSee('Cash Drawer')
            ->assertSee('Save Layaway')
            ->assertSee('Return / Exchange')
            ->assertSee('Gift Card')
            ->assertSee('Store Credit')
            ->assertSee('POS-SCAN-1');
    }

    public function test_retail_pos_sale_posts_split_payments_and_updates_shared_services(): void
    {
        $branch = Branch::create(['name' => 'Main Store', 'code' => 'MAIN', 'is_active' => true]);
        $client = Client::create(['name' => 'POS Customer', 'email' => 'pos-customer@example.test']);
        $product = $this->product('POS-SALE-1', 100, 5);
        $cash = PaymentMethod::create(['name' => 'Cash', 'type' => 'Cash', 'is_active' => true]);
        $card = PaymentMethod::create(['name' => 'Card', 'type' => 'Card', 'is_active' => true]);
        $giftCardMethod = PaymentMethod::create(['name' => 'Gift Card', 'type' => 'Gift Card', 'is_active' => true]);
        $giftCard = app(RetailGiftCardService::class)->issue(80, $client);

        RetailPromotion::create([
            'name' => 'POS Ten Percent',
            'promotion_type' => 'Percentage Discount',
            'discount_value' => 10,
            'status' => 'Active',
        ]);

        $this->post(route('retail.pos.drawers.open'), [
            'branch_id' => $branch->id,
            'drawer_number' => 'REG-1',
            'opening_float' => 25,
        ])->assertSessionHas('status');

        $drawer = RetailCashDrawer::firstOrFail();

        $this->post(route('retail.pos.sales.store'), [
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'retail_cash_drawer_id' => $drawer->id,
            'sale_type' => 'Sale',
            'channel' => 'Store',
            'coupon_code' => 'SAVE10',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100,
                'discount' => 0,
                'tax_rate' => 10,
            ]],
            'payments' => [
                ['payment_method_id' => $cash->id, 'method_type' => 'Cash', 'amount' => 100, 'reference' => 'CASH-1'],
                ['payment_method_id' => $card->id, 'method_type' => 'Card', 'amount' => 48, 'reference' => 'CARD-1'],
                ['payment_method_id' => $giftCardMethod->id, 'method_type' => 'Gift Card', 'amount' => 50, 'reference' => 'GC-1', 'retail_gift_card_id' => $giftCard->id],
            ],
        ])->assertSessionHas('status');

        $order = PosOrder::with('items', 'payments', 'retailExtension')->firstOrFail();

        $this->assertSame('paid', $order->status);
        $this->assertEquals(198.00, (float) $order->total);
        $this->assertEquals(198.00, (float) $order->amount_paid);
        $this->assertCount(3, $order->payments);
        $this->assertSame('3.000', $product->fresh()->stock_quantity);
        $this->assertSame('30.00', $giftCard->fresh()->balance);
        $this->assertSame(198, RetailLoyaltyAccount::where('client_id', $client->id)->firstOrFail()->points_balance);
        $this->assertSame('100.00', $drawer->fresh()->cash_sales);
        $this->assertSame('125.00', $drawer->fresh()->expected_cash);
        $this->assertSame('SAVE10', RetailSalesExtension::firstOrFail()->coupon_code);

        $this->post(route('retail.pos.orders.void', $order), ['reason' => 'Cashier correction'])->assertSessionHas('status');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('5.000', $product->fresh()->stock_quantity);
        $this->assertNotNull($order->fresh()->retailExtension->voided_at);
    }

    public function test_ecommerce_integration_exposes_website_product_category_and_pricing_feed(): void
    {
        $product = $this->product('WEB-SKU-1', 150, 7);
        $product->retailProfile()->create([
            'barcode' => 'WEB-BAR-1',
            'brand' => 'Web Brand',
            'product_type' => 'Physical Product',
            'tax_class' => '16',
            'status' => 'Active',
            'currency_prices' => ['default' => ['currency' => 'KES', 'price' => 150]],
        ]);

        $this->post(route('retail.ecommerce.store'), [
            'channel' => 'Website',
            'external_store_id' => 'WEB-1',
            'website_url' => 'https://shop.example.test',
            'status' => 'Active',
            'product_sync' => 1,
            'inventory_sync' => 1,
            'order_sync' => 1,
            'customer_sync' => 1,
        ])->assertSessionHas('status');

        $integration = RetailEcommerceIntegration::firstOrFail();
        $apiKey = data_get($integration->settings, 'api_key');

        $this->assertNotEmpty($apiKey);

        $this->getJson(route('api.v1.public.retail.ecommerce.products', $integration->id).'?api_key='.$apiKey)
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'WEB-SKU-1')
            ->assertJsonPath('data.0.category.name', 'Retail Sections')
            ->assertJsonPath('data.0.pricing.selling_price', 150)
            ->assertJsonPath('data.0.pricing.currency', 'KES');

        $this->getJson(route('api.v1.public.retail.ecommerce.categories', $integration->id).'?api_key='.$apiKey)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Retail Sections');

        $this->getJson(route('api.v1.public.retail.ecommerce.pricing', $integration->id).'?api_key='.$apiKey)
            ->assertOk()
            ->assertJsonPath('data.0.pricing.selling_price', 150)
            ->assertJsonPath('data.0.inventory.shared_stock', 7);

        $this->getJson(route('api.v1.public.retail.ecommerce.products', $integration->id).'?api_key=wrong')
            ->assertUnauthorized();
    }

    public function test_enterprise_retail_features_cover_inventory_omnichannel_crm_procurement_and_compliance(): void
    {
        $branch = Branch::create(['name' => 'Enterprise Store', 'code' => 'ENT', 'is_active' => true]);
        $supplier = Supplier::create(['name' => 'Enterprise Supplier', 'email' => 'enterprise-supplier@example.test']);
        $client = Client::create(['name' => 'Enterprise Customer', 'email' => 'enterprise-customer@example.test']);
        $product = $this->product('ENT-SKU-1', 120, 8);
        $warehouse = RetailWarehouse::create([
            'branch_id' => $branch->id,
            'code' => 'ENT-WH',
            'name' => 'Enterprise Warehouse',
            'warehouse_type' => 'Store Warehouse',
            'status' => 'Active',
        ]);
        $bin = RetailWarehouseBin::create([
            'retail_warehouse_id' => $warehouse->id,
            'bin_code' => 'A-01-01',
            'aisle' => 'A',
            'shelf' => '01',
            'status' => 'Active',
        ]);
        RetailInventoryBalance::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'retail_warehouse_id' => $warehouse->id,
            'retail_warehouse_bin_id' => $bin->id,
            'available_stock' => 2,
            'unit_cost' => 40,
        ]);
        $pos = PosOrder::create([
            'client_id' => $client->id,
            'order_number' => 'POS-ENT-1',
            'tracking_key' => 'ENTTRACK1',
            'order_date' => now(),
            'customer_name' => $client->name,
            'status' => 'paid',
            'subtotal' => 360,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 360,
            'amount_paid' => 360,
        ]);
        $pos->items()->create([
            'product_id' => $product->id,
            'title' => $product->name,
            'description' => $product->name,
            'quantity' => 3,
            'unit_price' => 120,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => 360,
        ]);

        $this->post(route('retail.inventory.replenishment'), [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'retail_warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'forecast_period_days' => 30,
            'lead_time_days' => 10,
            'safety_stock_factor' => 2,
        ])->assertSessionHas('status');

        $plan = RetailReplenishmentPlan::firstOrFail();
        $this->assertGreaterThan(0, (float) $plan->safety_stock_qty);
        $this->post(route('retail.inventory.replenishment.purchase-order', $plan))->assertSessionHas('status');
        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id]);

        $this->post(route('retail.inventory.cycle-counts.store'), [
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'retail_warehouse_id' => $warehouse->id,
            'retail_warehouse_bin_id' => $bin->id,
            'counted_quantity' => 1,
            'notes' => 'Cycle count variance',
        ])->assertSessionHas('status');
        $this->assertSame('-1.000', RetailCycleCount::firstOrFail()->variance_quantity);

        $this->post(route('retail.suppliers.contracts.store', $supplier), [
            'product_id' => $product->id,
            'contract_number' => 'CON-ENT-1',
            'payment_terms' => 'Net 30',
            'lead_time_days' => 10,
            'service_level_agreement' => '98% on-time delivery',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('retail_supplier_contracts', ['contract_number' => 'CON-ENT-1']);

        $this->post(route('retail.customers.offers.store'), [
            'client_id' => $client->id,
            'offer_name' => 'Basket Builder',
            'status' => 'Active',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('retail_customer_offers', ['offer_name' => 'Basket Builder']);

        $this->post(route('retail.orders.store'), [
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'channel' => 'Online Store',
            'status' => 'Confirmed',
            'items' => [[
                'product_id' => $product->id,
                'title' => $product->name,
                'quantity' => 1,
                'unit_price' => 120,
                'discount' => 0,
                'tax_rate' => 0,
            ]],
        ])->assertSessionHas('status');

        $order = \Modules\Retail\Models\RetailOrder::firstOrFail();
        $this->post(route('retail.orders.fulfillment.route', $order), [
            'fulfillment_type' => 'BOPIS',
            'branch_id' => $branch->id,
            'retail_warehouse_id' => $warehouse->id,
            'routing_status' => 'Ready For Pickup',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('retail_order_fulfillments', ['retail_order_id' => $order->id, 'fulfillment_type' => 'BOPIS']);

        $this->post(route('retail.settings.tax-jurisdictions.store'), [
            'country' => 'KE',
            'region' => 'Nairobi',
            'tax_name' => 'VAT',
            'tax_code' => 'KE-VAT',
            'tax_rate' => 16,
            'currency_code' => 'KES',
            'status' => 'Active',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('retail_tax_jurisdictions', ['tax_code' => 'KE-VAT', 'currency_code' => 'KES']);

        $this->get(route('retail.analytics.index'))->assertOk()->assertSee('SKU Profitability');
        $this->get(route('retail.reports.index'))->assertOk()->assertSee('VAT/GST Jurisdictions')->assertSee('BOPIS Fulfillment');

        $this->assertTrue(PurchaseOrder::exists());
        $this->assertTrue(RetailOrderFulfillment::exists());
        $this->assertTrue(RetailCustomerOffer::exists());
        $this->assertTrue(RetailSupplierContract::exists());
        $this->assertTrue(RetailTaxJurisdiction::exists());
    }

    private function product(string $sku, float $price, float $stock): Product
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Retail Sections']);

        return Product::create([
            'product_category_id' => $category->id,
            'name' => 'Retail Section Product',
            'sku' => $sku,
            'price' => $price,
            'cost_price' => 40,
            'stock_quantity' => $stock,
            'reorder_level' => 2,
            'stock_unit' => 'pcs',
            'is_active' => true,
        ]);
    }
}
