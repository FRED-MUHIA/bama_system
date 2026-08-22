<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('retail_replenishment_plans')) {
            Schema::create('retail_replenishment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('forecast_period_days')->default(30);
                $table->decimal('average_daily_demand', 14, 3)->default(0);
                $table->decimal('demand_forecast_qty', 14, 3)->default(0);
                $table->unsignedInteger('lead_time_days')->default(0);
                $table->decimal('safety_stock_qty', 14, 3)->default(0);
                $table->decimal('reorder_point_qty', 14, 3)->default(0);
                $table->decimal('available_stock_qty', 14, 3)->default(0);
                $table->decimal('recommended_order_qty', 14, 3)->default(0);
                $table->decimal('landed_cost_per_unit', 14, 2)->default(0);
                $table->decimal('estimated_total_cost', 14, 2)->default(0);
                $table->string('status')->default('Proposed');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_cycle_counts')) {
            Schema::create('retail_cycle_counts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->foreignId('retail_warehouse_bin_id')->nullable()->constrained('retail_warehouse_bins')->nullOnDelete();
                $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('counted_at')->nullable();
                $table->decimal('system_quantity', 14, 3)->default(0);
                $table->decimal('counted_quantity', 14, 3)->default(0);
                $table->decimal('variance_quantity', 14, 3)->default(0);
                $table->string('status')->default('Scheduled');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_order_fulfillments')) {
            Schema::create('retail_order_fulfillments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_order_id')->constrained('retail_orders')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('retail_warehouse_id')->nullable()->constrained('retail_warehouses')->nullOnDelete();
                $table->string('fulfillment_type')->default('BOPIS');
                $table->string('routing_status')->default('Routed');
                $table->dateTime('routed_at')->nullable();
                $table->dateTime('picked_at')->nullable();
                $table->dateTime('packed_at')->nullable();
                $table->dateTime('ready_for_pickup_at')->nullable();
                $table->dateTime('shipped_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->string('carrier')->nullable();
                $table->string('tracking_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_customer_offers')) {
            Schema::create('retail_customer_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('retail_promotion_id')->nullable()->constrained('retail_promotions')->nullOnDelete();
                $table->string('offer_name');
                $table->string('offer_type')->default('Personalized Offer');
                $table->string('segment')->nullable();
                $table->json('behavior_summary')->nullable();
                $table->json('recommended_products')->nullable();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->unsignedInteger('redemption_count')->default(0);
                $table->string('status')->default('Draft');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('retail_supplier_contracts')) {
            Schema::create('retail_supplier_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('contract_number');
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->string('payment_terms')->nullable();
                $table->unsignedInteger('lead_time_days')->default(0);
                $table->string('service_level_agreement')->nullable();
                $table->json('scorecard')->nullable();
                $table->json('landed_cost_components')->nullable();
                $table->string('status')->default('Active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'contract_number']);
            });
        }

        if (! Schema::hasTable('retail_tax_jurisdictions')) {
            Schema::create('retail_tax_jurisdictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('country');
                $table->string('region')->nullable();
                $table->string('tax_name')->default('VAT');
                $table->string('tax_code')->nullable();
                $table->decimal('tax_rate', 8, 4)->default(0);
                $table->string('currency_code', 3)->default('KES');
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->string('status')->default('Active');
                $table->timestamps();
                $table->index(['business_id', 'country', 'region', 'status'], 'retail_tax_jurisdiction_lookup');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'retail_tax_jurisdictions',
            'retail_supplier_contracts',
            'retail_customer_offers',
            'retail_order_fulfillments',
            'retail_cycle_counts',
            'retail_replenishment_plans',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
