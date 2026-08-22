<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('customer_phone');
            $table->text('customer_address')->nullable()->after('customer_email');
            $table->decimal('custom_amount', 12, 2)->default(0)->after('tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn(['customer_email', 'customer_address', 'custom_amount']);
        });
    }
};
