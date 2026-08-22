<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospitality_restaurant_orders')) {
            return;
        }

        Schema::table('hospitality_restaurant_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('hospitality_restaurant_orders', 'reserved_for')) {
                $table->dateTime('reserved_for')->nullable()->after('table_number');
            }

            if (! Schema::hasColumn('hospitality_restaurant_orders', 'party_size')) {
                $table->unsignedSmallInteger('party_size')->default(1)->after('reserved_for');
            }

            if (! Schema::hasColumn('hospitality_restaurant_orders', 'order_type')) {
                $table->string('order_type')->default('Table Reservation')->after('party_size');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hospitality_restaurant_orders')) {
            return;
        }

        Schema::table('hospitality_restaurant_orders', function (Blueprint $table) {
            foreach (['reserved_for', 'party_size', 'order_type'] as $column) {
                if (Schema::hasColumn('hospitality_restaurant_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
