<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('retail_expenses')) {
            return;
        }

        Schema::create('retail_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expense_date');
            $table->string('category', 120);
            $table->string('description', 1000);
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->string('vendor')->nullable();
            $table->string('reference', 120)->nullable();
            $table->timestamps();
            $table->index(['business_id', 'expense_date']);
            $table->index(['business_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_expenses');
    }
};
