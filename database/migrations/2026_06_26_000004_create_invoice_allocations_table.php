<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_allocations')) {
            return;
        }

        Schema::create('invoice_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_allocations');
    }
};
