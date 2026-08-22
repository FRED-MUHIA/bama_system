<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospitality_restaurant_purchases')) {
            return;
        }

        Schema::table('hospitality_restaurant_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('hospitality_restaurant_purchases', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('purchase_number')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hospitality_restaurant_purchases') || ! Schema::hasColumn('hospitality_restaurant_purchases', 'supplier_id')) {
            return;
        }

        Schema::table('hospitality_restaurant_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
