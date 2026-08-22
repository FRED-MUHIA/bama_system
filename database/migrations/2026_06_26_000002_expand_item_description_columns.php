<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->alterDescriptionColumns('TEXT');
    }

    public function down(): void
    {
        $this->alterDescriptionColumns('VARCHAR(255)');
    }

    private function alterDescriptionColumns(string $type): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['quotation_items', 'invoice_items', 'pos_order_items'] as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `description` {$type} NOT NULL");
        }
    }
};
