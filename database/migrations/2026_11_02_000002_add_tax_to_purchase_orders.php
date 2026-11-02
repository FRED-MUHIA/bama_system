<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders') && ! Schema::hasColumn('purchase_orders', 'tax_amount')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('amount');
            });
        }

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_order_items', 'tax_rate')) {
                    $table->decimal('tax_rate', 5, 2)->default(0)->after('unit_price');
                }
                if (! Schema::hasColumn('purchase_order_items', 'tax_amount')) {
                    $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'tax_amount')) {
            Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropColumn('tax_amount'));
        }

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $columns = array_filter(['tax_rate', 'tax_amount'], fn ($column) => Schema::hasColumn('purchase_order_items', $column));
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
