<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        ['pos_orders', ['business_id', 'order_date'], 'pos_orders_business_date_idx'],
        ['pos_orders', ['business_id', 'status'], 'pos_orders_business_status_idx'],
        ['pos_order_items', ['pos_order_id'], 'pos_order_items_order_idx'],
        ['invoices', ['business_id', 'created_at'], 'invoices_business_created_idx'],
        ['invoices', ['business_id', 'payment_status'], 'invoices_business_payment_status_idx'],
        ['invoice_items', ['invoice_id'], 'invoice_items_invoice_idx'],
        ['payments', ['invoice_id'], 'payments_invoice_idx'],
        ['receipts', ['invoice_id'], 'receipts_invoice_idx'],
        ['products', ['business_id', 'is_active'], 'products_business_active_idx'],
        ['clients', ['business_id', 'created_at'], 'clients_business_created_idx'],
        ['stock_movements', ['business_id', 'created_at'], 'stock_movements_business_created_idx'],
        ['retail_orders', ['business_id', 'created_at'], 'retail_orders_business_created_idx'],
        ['retail_order_items', ['retail_order_id'], 'retail_order_items_order_idx'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $columns, $name]) {
            if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                $blueprint->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $columns, $name]) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropIndex($name);
            });
        }
    }
};
