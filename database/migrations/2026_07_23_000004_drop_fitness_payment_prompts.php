<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fitness_payment_prompts');
    }

    public function down(): void
    {
        if (! Schema::hasTable('fitness_payment_prompts')) {
            Schema::create('fitness_payment_prompts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_membership_id')->constrained('fitness_member_memberships')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
                $table->string('phone_number', 32);
                $table->decimal('amount', 14, 2);
                $table->string('provider')->default('manual_phone_prompt');
                $table->string('status')->default('Pending');
                $table->string('external_reference')->nullable();
                $table->string('checkout_request_id')->nullable();
                $table->string('merchant_request_id')->nullable();
                $table->string('customer_message')->nullable();
                $table->text('failure_reason')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'fit_pay_prompts_business_status_idx');
                $table->index(['business_id', 'phone_number'], 'fit_pay_prompts_business_phone_idx');
                $table->index('checkout_request_id', 'fit_pay_prompts_checkout_idx');
            });
        }
    }
};
