<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospitality_restaurant_tables')) {
            Schema::create('hospitality_restaurant_tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('table_number');
                $table->string('section')->nullable();
                $table->unsignedSmallInteger('capacity')->default(2);
                $table->string('status')->default('Available')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'table_number'], 'hosp_rest_tables_biz_number_unique');
            });
        }

        if (! Schema::hasTable('hospitality_units')) {
            Schema::create('hospitality_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('symbol', 20);
                $table->string('type')->default('Quantity');
                $table->timestamps();
                $table->unique(['business_id', 'symbol'], 'hosp_units_biz_symbol_unique');
            });
        }

        if (! Schema::hasTable('hospitality_ingredients')) {
            Schema::create('hospitality_ingredients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('hospitality_units')->nullOnDelete();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->decimal('on_hand', 14, 3)->default(0);
                $table->decimal('reorder_level', 14, 3)->default(0);
                $table->decimal('cost_per_unit', 14, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['business_id', 'name'], 'hosp_ingredients_biz_name_unique');
            });
        }

        if (! Schema::hasTable('hospitality_menu_item_ingredients')) {
            Schema::create('hospitality_menu_item_ingredients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ingredient_id')->constrained('hospitality_ingredients')->cascadeOnDelete();
                $table->decimal('quantity', 14, 3)->default(1);
                $table->timestamps();
                $table->unique(['product_id', 'ingredient_id'], 'hosp_recipe_product_ingredient_unique');
            });
        }

        if (! Schema::hasTable('hospitality_restaurant_purchases')) {
            Schema::create('hospitality_restaurant_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('purchase_number')->unique();
                $table->string('supplier_name');
                $table->string('status')->default('Draft')->index();
                $table->string('shipping_method')->nullable();
                $table->date('expected_at')->nullable();
                $table->decimal('total', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hospitality_restaurant_purchase_items')) {
            Schema::create('hospitality_restaurant_purchase_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_purchase_id');
                $table->unsignedBigInteger('ingredient_id')->nullable();
                $table->string('description');
                $table->decimal('quantity', 14, 3)->default(1);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('line_total', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('hospitality_restaurant_purchase_items') && DB::getDriverName() === 'mysql') {
            $foreignKeys = collect(DB::select(
                "select constraint_name from information_schema.key_column_usage where table_schema = database() and table_name = ? and referenced_table_name is not null",
                ['hospitality_restaurant_purchase_items']
            ))->map(fn ($row) => $row->constraint_name ?? $row->CONSTRAINT_NAME ?? null)->filter()->all();

            Schema::table('hospitality_restaurant_purchase_items', function (Blueprint $table) use ($foreignKeys) {
                if (! in_array('hosp_purchase_items_purchase_fk', $foreignKeys, true)) {
                    $table->foreign('restaurant_purchase_id', 'hosp_purchase_items_purchase_fk')
                        ->references('id')
                        ->on('hospitality_restaurant_purchases')
                        ->cascadeOnDelete();
                }

                if (! in_array('hosp_purchase_items_ingredient_fk', $foreignKeys, true)) {
                    $table->foreign('ingredient_id', 'hosp_purchase_items_ingredient_fk')
                        ->references('id')
                        ->on('hospitality_ingredients')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('hospitality_restaurant_orders')) {
            Schema::table('hospitality_restaurant_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('hospitality_restaurant_orders', 'restaurant_table_id')) {
                    $table->foreignId('restaurant_table_id')->nullable()->after('pos_order_id')->constrained('hospitality_restaurant_tables')->nullOnDelete();
                }

                if (! Schema::hasColumn('hospitality_restaurant_orders', 'payment_method_id')) {
                    $table->foreignId('payment_method_id')->nullable()->after('waiter_id')->constrained()->nullOnDelete();
                }

                if (! Schema::hasColumn('hospitality_restaurant_orders', 'shipping_method')) {
                    $table->string('shipping_method')->nullable()->after('payment_method_id');
                }

                foreach (['kitchen_started_at', 'kitchen_ready_at', 'kitchen_served_at'] as $column) {
                    if (! Schema::hasColumn('hospitality_restaurant_orders', $column)) {
                        $table->timestamp($column)->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hospitality_restaurant_orders')) {
            Schema::table('hospitality_restaurant_orders', function (Blueprint $table) {
                foreach (['kitchen_started_at', 'kitchen_ready_at', 'kitchen_served_at', 'shipping_method'] as $column) {
                    if (Schema::hasColumn('hospitality_restaurant_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }

                foreach (['payment_method_id', 'restaurant_table_id'] as $column) {
                    if (Schema::hasColumn('hospitality_restaurant_orders', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }

        Schema::dropIfExists('hospitality_restaurant_purchase_items');
        Schema::dropIfExists('hospitality_restaurant_purchases');
        Schema::dropIfExists('hospitality_menu_item_ingredients');
        Schema::dropIfExists('hospitality_ingredients');
        Schema::dropIfExists('hospitality_units');
        Schema::dropIfExists('hospitality_restaurant_tables');
    }
};
