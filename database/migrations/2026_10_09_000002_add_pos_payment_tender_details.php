<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_order_payments')) return;

        Schema::table('pos_order_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_order_payments', 'method_type')) $table->string('method_type', 100)->nullable();
            if (Schema::hasTable('retail_gift_cards') && ! Schema::hasColumn('pos_order_payments', 'retail_gift_card_id')) $table->foreignId('retail_gift_card_id')->nullable()->constrained('retail_gift_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pos_order_payments')) return;

        Schema::table('pos_order_payments', function (Blueprint $table) {
            if (Schema::hasColumn('pos_order_payments', 'retail_gift_card_id')) $table->dropConstrainedForeignId('retail_gift_card_id');
            if (Schema::hasColumn('pos_order_payments', 'method_type')) $table->dropColumn('method_type');
        });
    }
};
