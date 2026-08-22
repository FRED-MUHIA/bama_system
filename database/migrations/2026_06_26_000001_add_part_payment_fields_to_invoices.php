<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('parent_invoice_id')->nullable()->after('quotation_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('part_payment_amount', 12, 2)->default(0)->after('parent_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_invoice_id');
            $table->dropColumn('part_payment_amount');
        });
    }
};
