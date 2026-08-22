<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceUnique('quotations', 'quotation_number');
        $this->replaceUnique('invoices', 'invoice_number');
        $this->replaceUnique('receipts', 'receipt_number');
        $this->replaceUnique('pos_orders', 'order_number');
        $this->replaceUnique('product_categories', 'name');
        $this->replaceUnique('products', 'sku');
    }

    public function down(): void
    {
        $this->restoreUnique('products', 'sku');
        $this->restoreUnique('product_categories', 'name');
        $this->restoreUnique('pos_orders', 'order_number');
        $this->restoreUnique('receipts', 'receipt_number');
        $this->restoreUnique('invoices', 'invoice_number');
        $this->restoreUnique('quotations', 'quotation_number');
    }

    private function replaceUnique(string $tableName, string $column): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropUnique([$column]);
            $table->unique(['business_id', $column]);
        });
    }

    private function restoreUnique(string $tableName, string $column): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropUnique(['business_id', $column]);
            $table->unique($column);
        });
    }
};
