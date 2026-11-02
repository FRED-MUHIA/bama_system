<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_orders', 'amount')) {
                    $table->decimal('amount', 15, 2)->default(0)->change();
                }

                if (Schema::hasColumn('purchase_orders', 'tax_amount')) {
                    $table->decimal('tax_amount', 15, 2)->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                foreach (['unit_price', 'line_total', 'tax_amount'] as $column) {
                    if (Schema::hasColumn('purchase_order_items', $column)) {
                        $table->decimal($column, 15, 2)->default(0)->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_orders', 'amount')) {
                    $table->decimal('amount', 12, 2)->default(0)->change();
                }

                if (Schema::hasColumn('purchase_orders', 'tax_amount')) {
                    $table->decimal('tax_amount', 12, 2)->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                foreach (['unit_price', 'line_total', 'tax_amount'] as $column) {
                    if (Schema::hasColumn('purchase_order_items', $column)) {
                        $table->decimal($column, 12, 2)->default(0)->change();
                    }
                }
            });
        }
    }
};
