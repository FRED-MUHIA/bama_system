<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_orders', 'invoice_id')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropUnique('pos_orders_invoice_id_unique');
            });

            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_id');
            });
        }

        if (Schema::hasColumn('pos_orders', 'approved_at')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
