<?php

use App\Support\DatabasePlatform;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'stock_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('stock_unit', 30)->default('pcs')->after('reorder_level');
            });
        }

        DatabasePlatform::alterNumericColumn('products', 'stock_quantity', 'DECIMAL(14,3)');

        if (Schema::hasColumn('products', 'reorder_level')) {
            DatabasePlatform::alterNumericColumn('products', 'reorder_level', 'DECIMAL(14,3)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'stock_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('stock_unit');
            });
        }

        DatabasePlatform::alterNumericColumn('products', 'stock_quantity', 'INT', '0', false, 'ROUND(stock_quantity)::INTEGER');

        if (Schema::hasColumn('products', 'reorder_level')) {
            DatabasePlatform::alterNumericColumn('products', 'reorder_level', 'INT', '0', false, 'ROUND(reorder_level)::INTEGER');
        }
    }
};
