<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->tables();
        $this->registerRetail();
    }

    public function down(): void
    {
        foreach ([
            'retail_deliveries',
            'retail_order_items',
            'retail_orders',
            'retail_ecommerce_integrations',
            'retail_sales_extensions',
            'retail_cash_drawers',
            'retail_return_items',
            'retail_return_authorizations',
            'retail_gift_card_transactions',
            'retail_gift_cards',
            'retail_promotions',
            'retail_loyalty_transactions',
            'retail_loyalty_accounts',
            'retail_customer_profiles',
            'retail_inventory_movements',
            'retail_inventory_balances',
            'retail_warehouse_bins',
            'retail_warehouse_zones',
            'retail_warehouses',
            'retail_product_bundles',
            'retail_product_variants',
            'retail_product_profiles',
            'retail_supplier_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function tables(): void
    {
        if (! Schema::hasTable('retail_supplier_profiles')) {
            Schema::create('retail_supplier_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('supplier_code')->nullable();
                $table->string('tax_information')->nullable();
                $table->string('payment_terms')->nullable();
                $table->unsignedInteger('lead_time_days')->default(0);
                $table->decimal('delivery_accuracy', 6, 2)->default(0);
                $table->decimal('rating', 4, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'supplier_id']);
            });
        }

        if (! Schema::hasTable('retail_product_profiles')) {
            Schema::create('retail_product_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->string('barcode')->nullable();
                $table->string('brand')->nullable();
                $table->string('tax_class')->nullable();
                $table->string('product_type')->default('Physical Product');
                $table->string('status')->default('Active');
                $table->json('images')->nullable();
                $table->json('attributes')->nullable();
                $table->json('tags')->nullable();
                $table->json('localized_content')->nullable();
                $table->json('currency_prices')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'product_id']);
                $table->index(['business_id', 'barcode']);
                $table->index(['business_id', 'brand']);
            });
        }

        if (! Schema::hasTable('retail_product_variants')) {
            Schema::create('retail_product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('sku')->nullable();
                $table->string('barcode')->nullable();
                $table->json('attributes')->nullable();
                $table->decimal('price_delta', 14, 2)->default(0);
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->unique(['business_id', 'parent_product_id', 'product_id'], 'retail_variant_unique');
            });
        }

        if (! Schema::hasTable('retail_product_bundles')) {
            Schema::create('retail_product_bundles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bundle_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
                $table->decimal('quantity', 14, 3)->default(1);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->timestamps();
                $table->unique(['business_id', 'bundle_product_id', 'component_product_id'], 'retail_bundle_unique');
            });
        }

        if (! Schema::hasTable('retail_warehouses')) {
            Schema::create('retail_warehouses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code');
                $table->string('name');
                $table->string('warehouse_type')->default('Store Warehouse');
                $table->string('address')->nullable();
                $table->decimal('capacity', 14, 3)->default(0);
                $table->string('status')->default('Active');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'code']);
            });
        }

        if (! Schema::hasTable('retail_warehouse_zones')) {
            Schema::create('retail_warehouse_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_warehouse_id')->constrained('retail_warehouses')->cascadeOnDelete();
                $table->string('code');
                $table->string('name');
                $table->string('zone_type')->nullable();
                $table->timestamps();
                $table->unique(['retail_warehouse_id', 'code']);
            });
        }

        if (! Schema::hasTable('retail_warehouse_bins')) {
            Schema::create('retail_warehouse_bins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_warehouse_id')->constrained('retail_warehouses')->cascadeOnDelete();
                $table->foreignId('retail_warehouse_zone_id')->nullable()->constrained('retail_warehouse_zones')->nullOnDelete();
                $table->string('aisle')->nullable();
                $table->string('shelf')->nullable();
                $table->string('bin_code');
                $table->decimal('capacity', 14, 3)->default(0);
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->unique(['retail_warehouse_id', 'bin_code']);
            });
        }

        if (! Schema::hasTable('retail_inventory_balances')) {
            Schema::create('retail_inventory_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->foreignId('retail_warehouse_bin_id')->nullable()->constrained('retail_warehouse_bins')->nullOnDelete();
                $table->decimal('available_stock', 14, 3)->default(0);
                $table->decimal('reserved_stock', 14, 3)->default(0);
                $table->decimal('in_transit_stock', 14, 3)->default(0);
                $table->decimal('damaged_stock', 14, 3)->default(0);
                $table->decimal('reorder_level', 14, 3)->default(0);
                $table->string('valuation_method')->default('FIFO');
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('stock_value', 14, 2)->default(0);
                $table->timestamps();
                $table->index(['business_id', 'product_id']);
                $table->unique(['business_id', 'product_id', 'branch_id', 'retail_warehouse_id', 'retail_warehouse_bin_id'], 'retail_inventory_balance_unique');
            });
        }

        if (! Schema::hasTable('retail_inventory_movements')) {
            Schema::create('retail_inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->foreignId('retail_warehouse_bin_id')->nullable()->constrained('retail_warehouse_bins')->nullOnDelete();
                $table->string('type');
                $table->decimal('quantity', 14, 3);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('balance_after', 14, 3)->default(0);
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['source_type', 'source_id']);
                $table->index(['business_id', 'type']);
            });
        }

        if (! Schema::hasTable('retail_customer_profiles')) {
            Schema::create('retail_customer_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('loyalty_number')->nullable();
                $table->string('customer_segment')->default('Retail Customer');
                $table->json('shopping_preferences')->nullable();
                $table->decimal('lifetime_value', 14, 2)->default(0);
                $table->unsignedInteger('total_purchases')->default(0);
                $table->text('customer_notes')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'client_id']);
                $table->unique(['business_id', 'loyalty_number']);
            });
        }

        if (! Schema::hasTable('retail_loyalty_accounts')) {
            Schema::create('retail_loyalty_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('loyalty_number');
                $table->string('tier')->default('Bronze');
                $table->integer('points_balance')->default(0);
                $table->integer('points_earned')->default(0);
                $table->integer('points_redeemed')->default(0);
                $table->decimal('cashback_balance', 14, 2)->default(0);
                $table->date('joined_at')->nullable();
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->unique(['business_id', 'client_id']);
                $table->unique(['business_id', 'loyalty_number']);
            });
        }

        if (! Schema::hasTable('retail_loyalty_transactions')) {
            Schema::create('retail_loyalty_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_loyalty_account_id')->constrained('retail_loyalty_accounts')->cascadeOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->string('type');
                $table->integer('points')->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('reason')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_promotions')) {
            Schema::create('retail_promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('promotion_type');
                $table->string('code')->nullable();
                $table->decimal('discount_value', 14, 2)->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->json('product_eligibility')->nullable();
                $table->json('customer_eligibility')->nullable();
                $table->json('store_eligibility')->nullable();
                $table->json('metadata')->nullable();
                $table->string('status')->default('Draft');
                $table->timestamps();
                $table->index(['business_id', 'status', 'starts_at', 'ends_at'], 'retail_promo_schedule_index');
            });
        }

        if (! Schema::hasTable('retail_gift_cards')) {
            Schema::create('retail_gift_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->string('card_number');
                $table->decimal('issued_amount', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->string('currency', 3)->default('KES');
                $table->date('expires_at')->nullable();
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->unique(['business_id', 'card_number']);
            });
        }

        if (! Schema::hasTable('retail_gift_card_transactions')) {
            Schema::create('retail_gift_card_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_gift_card_id')->constrained('retail_gift_cards')->cascadeOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->string('type');
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('balance_after', 14, 2)->default(0);
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_return_authorizations')) {
            Schema::create('retail_return_authorizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->string('return_number');
                $table->string('return_type')->default('Return');
                $table->string('reason');
                $table->string('status')->default('Pending');
                $table->string('approval_status')->default('Pending');
                $table->dateTime('requested_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('refund_method')->default('Original Payment');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('refund_total', 14, 2)->default(0);
                $table->timestamps();
                $table->unique(['business_id', 'return_number']);
            });
        }

        if (! Schema::hasTable('retail_return_items')) {
            Schema::create('retail_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_return_authorization_id')->constrained('retail_return_authorizations')->cascadeOnDelete();
                $table->foreignId('pos_order_item_id')->nullable()->constrained('pos_order_items')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('quantity', 14, 3)->default(1);
                $table->string('condition')->default('Resellable');
                $table->decimal('refund_amount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_cash_drawers')) {
            Schema::create('retail_cash_drawers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('drawer_number');
                $table->dateTime('opened_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->decimal('opening_float', 14, 2)->default(0);
                $table->decimal('cash_sales', 14, 2)->default(0);
                $table->decimal('cash_refunds', 14, 2)->default(0);
                $table->decimal('expected_cash', 14, 2)->default(0);
                $table->decimal('counted_cash', 14, 2)->default(0);
                $table->decimal('variance', 14, 2)->default(0);
                $table->string('status')->default('Open');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_sales_extensions')) {
            Schema::create('retail_sales_extensions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('retail_cash_drawer_id')->nullable()->constrained('retail_cash_drawers')->nullOnDelete();
                $table->foreignId('retail_promotion_id')->nullable()->constrained('retail_promotions')->nullOnDelete();
                $table->string('sale_type')->default('Sale');
                $table->string('channel')->default('Store');
                $table->string('coupon_code')->nullable();
                $table->json('split_payment_summary')->nullable();
                $table->dateTime('voided_at')->nullable();
                $table->date('layaway_due_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'pos_order_id']);
            });
        }

        if (! Schema::hasTable('retail_ecommerce_integrations')) {
            Schema::create('retail_ecommerce_integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('channel');
                $table->string('external_store_id')->nullable();
                $table->string('status')->default('Draft');
                $table->dateTime('last_product_sync_at')->nullable();
                $table->dateTime('last_inventory_sync_at')->nullable();
                $table->dateTime('last_order_sync_at')->nullable();
                $table->dateTime('last_customer_sync_at')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_orders')) {
            Schema::create('retail_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('pos_order_id')->nullable()->constrained('pos_orders')->nullOnDelete();
                $table->string('order_number');
                $table->string('channel')->default('Store');
                $table->date('order_date')->nullable();
                $table->string('status')->default('Draft');
                $table->dateTime('requested_delivery_at')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_total', 14, 2)->default(0);
                $table->decimal('tax_total', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'order_number']);
            });
        }

        if (! Schema::hasTable('retail_order_items')) {
            Schema::create('retail_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_order_id')->constrained('retail_orders')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('quantity', 14, 3)->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->decimal('tax_rate', 8, 2)->default(0);
                $table->decimal('line_total', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_deliveries')) {
            Schema::create('retail_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_order_id')->constrained('retail_orders')->cascadeOnDelete();
                $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tracking_number')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->string('delivery_address');
                $table->json('route_plan')->nullable();
                $table->json('tracking_events')->nullable();
                $table->string('status')->default('Scheduled');
                $table->timestamps();
            });
        }
    }

    private function registerRetail(): void
    {
        $now = now();
        $permissions = [
            'retail.view', 'retail.manage', 'retail.reports', 'retail.pos.view', 'retail.pos.manage',
            'retail.products.view', 'retail.products.manage', 'retail.inventory.view', 'retail.inventory.manage',
            'retail.warehousing.view', 'retail.warehousing.manage', 'retail.orders.view', 'retail.orders.manage',
            'retail.customers.view', 'retail.customers.manage', 'retail.loyalty.view', 'retail.loyalty.manage',
            'retail.promotions.view', 'retail.promotions.manage', 'retail.gift-cards.view', 'retail.gift-cards.manage',
            'retail.returns.view', 'retail.returns.manage', 'retail.procurement.view', 'retail.procurement.manage',
            'retail.suppliers.view', 'retail.suppliers.manage', 'retail.branches.view', 'retail.branches.manage',
            'retail.ecommerce.view', 'retail.ecommerce.manage', 'retail.analytics.view', 'retail.settings.manage',
        ];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'retail', 'description' => Str::headline(str_replace(['retail.', '.', '-'], ['', ' ', ' '], $permission)), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->updateOrInsert(
                ['slug' => 'retail'],
                [
                    'name' => 'Retail',
                    'namespace' => 'Modules\\Retail',
                    'type' => 'industry',
                    'industry' => 'retail',
                    'icon' => 'bi-shop',
                    'route' => 'retail.dashboard',
                    'permissions' => json_encode($permissions),
                    'menu' => json_encode(['label' => 'Retail', 'group' => 'Industry', 'icon' => 'bi-shop', 'route' => 'retail.dashboard']),
                    'widgets' => json_encode(['retail-sales-today', 'retail-low-stock', 'retail-loyalty-members']),
                    'is_core' => false,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $moduleId = DB::table('modules')->where('slug', 'retail')->value('id');
            if ($moduleId && Schema::hasTable('industry_modules')) {
                foreach (['retail', 'Retail', 'Retail Standard', 'Multi-Branch Retail', 'Enterprise Retail'] as $industry) {
                    DB::table('industry_modules')->updateOrInsert(
                        ['industry' => $industry, 'module_id' => $moduleId],
                        ['enabled_by_default' => true, 'updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['retail-sales-today', 'Retail Sales Today', 'retail.pos.view'],
                ['retail-average-basket', 'Retail Average Basket', 'retail.pos.view'],
                ['retail-low-stock', 'Retail Low Stock Alerts', 'retail.inventory.view'],
                ['retail-loyalty-members', 'Retail Loyalty Members', 'retail.loyalty.view'],
                ['retail-warehouse-fulfillment', 'Retail Fulfillment Rate', 'retail.warehousing.view'],
            ] as [$slug, $name, $permission]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'retail',
                        'industry' => 'retail',
                        'component' => 'retail.widgets.metric-card',
                        'permission' => $permission,
                        'settings_schema' => json_encode(['supports_period_filters' => true, 'supports_branch_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('iam_roles') && Schema::hasTable('iam_permission_role') && Schema::hasTable('businesses')) {
            $roleMap = $this->rolePermissionMap();
            foreach (DB::table('businesses')->pluck('id') as $businessId) {
                foreach ($roleMap as $slug => [$name, $rolePermissions]) {
                    DB::table('iam_roles')->updateOrInsert(
                        ['business_id' => $businessId, 'slug' => $slug],
                        ['name' => $name, 'is_system' => true, 'updated_at' => $now, 'created_at' => $now]
                    );

                    $roleId = DB::table('iam_roles')->where('business_id', $businessId)->where('slug', $slug)->value('id');
                    $permissionIds = DB::table('iam_permissions')->whereIn('name', $rolePermissions)->pluck('id');
                    foreach ($permissionIds as $permissionId) {
                        DB::table('iam_permission_role')->updateOrInsert(
                            ['iam_role_id' => $roleId, 'iam_permission_id' => $permissionId],
                            []
                        );
                    }
                }
            }
        }
    }

    private function rolePermissionMap(): array
    {
        return [
            'retail-director' => ['Retail Director', ['retail.view', 'retail.manage', 'retail.reports', 'retail.analytics.view', 'retail.pos.view', 'retail.products.view', 'retail.inventory.view', 'retail.warehousing.view', 'retail.orders.view', 'retail.customers.view', 'retail.loyalty.view', 'retail.promotions.view', 'retail.gift-cards.view', 'retail.returns.view', 'retail.procurement.view', 'retail.suppliers.view', 'retail.branches.view', 'retail.ecommerce.view']],
            'store-manager' => ['Store Manager', ['retail.view', 'retail.pos.manage', 'retail.products.manage', 'retail.inventory.manage', 'retail.customers.manage', 'retail.loyalty.manage', 'retail.promotions.view', 'retail.gift-cards.manage', 'retail.returns.manage', 'retail.reports']],
            'branch-manager' => ['Branch Manager', ['retail.view', 'retail.pos.view', 'retail.inventory.view', 'retail.orders.manage', 'retail.customers.manage', 'retail.branches.view', 'retail.reports']],
            'cashier' => ['Cashier', ['retail.view', 'retail.pos.manage', 'retail.products.view', 'retail.customers.view', 'retail.loyalty.view', 'retail.gift-cards.view', 'retail.returns.view']],
            'warehouse-manager' => ['Warehouse Manager', ['retail.view', 'retail.inventory.manage', 'retail.warehousing.manage', 'retail.orders.view', 'retail.procurement.view', 'retail.reports']],
            'warehouse-staff' => ['Warehouse Staff', ['retail.view', 'retail.inventory.view', 'retail.warehousing.view', 'retail.orders.view']],
            'customer-service' => ['Customer Service', ['retail.view', 'retail.customers.manage', 'retail.loyalty.view', 'retail.returns.manage', 'retail.orders.view']],
            'retail-accountant' => ['Retail Accountant', ['retail.view', 'retail.reports', 'retail.pos.view', 'retail.returns.view', 'finance.view', 'finance.gl.view']],
            'retail-auditor' => ['Retail Auditor', ['retail.view', 'retail.reports', 'retail.analytics.view', 'audit.view']],
        ];
    }
};
