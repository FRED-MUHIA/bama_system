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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->string('tracking_key', 64)->nullable()->unique()->after('order_number');
        });

        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('pos_order_id')->constrained()->nullOnDelete();
        });

        DB::table('pos_orders')->orderBy('id')->get(['id'])->each(function ($order) {
            DB::table('pos_orders')->where('id', $order->id)->update(['tracking_key' => Str::upper(Str::random(12))]);
        });
    }

    public function down(): void
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropUnique(['tracking_key']);
            $table->dropColumn('tracking_key');
        });

        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
