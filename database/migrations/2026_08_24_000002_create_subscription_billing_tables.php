<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (! Schema::hasColumn('subscriptions', 'grace_ends_at')) {
                    $table->timestamp('grace_ends_at')->nullable()->after('renews_at');
                }
                if (! Schema::hasColumn('subscriptions', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable()->after('ends_at');
                }
                if (! Schema::hasColumn('subscriptions', 'last_renewal_notice_sent_at')) {
                    $table->timestamp('last_renewal_notice_sent_at')->nullable()->after('locked_at');
                }
                if (! Schema::hasColumn('subscriptions', 'last_grace_notice_sent_at')) {
                    $table->timestamp('last_grace_notice_sent_at')->nullable()->after('last_renewal_notice_sent_at');
                }
            });
        }

        if (! Schema::hasTable('platform_payment_settings')) {
            Schema::create('platform_payment_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->unique();
                $table->boolean('is_enabled')->default(false);
                $table->string('mode')->default('sandbox');
                $table->string('public_key')->nullable();
                $table->text('secret_key')->nullable();
                $table->json('config')->nullable();
                $table->text('instructions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('billing_email')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('status')->default('pending');
                $table->string('currency', 3)->default('KES');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->index('due_at');
            });
        }

        if (! Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('provider');
                $table->string('status')->default('initiated');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 3)->default('KES');
                $table->string('checkout_request_id')->nullable()->index();
                $table->string('merchant_request_id')->nullable();
                $table->string('provider_order_id')->nullable()->index();
                $table->string('provider_payment_id')->nullable()->index();
                $table->string('provider_receipt')->nullable();
                $table->string('phone')->nullable();
                $table->text('payment_url')->nullable();
                $table->json('callback_payload')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'provider', 'status']);
            });
        }

        if (Schema::hasTable('platform_payment_settings')) {
            foreach (['mpesa', 'paypal', 'card'] as $provider) {
                DB::table('platform_payment_settings')->updateOrInsert(
                    ['provider' => $provider],
                    [
                        'mode' => 'sandbox',
                        'is_enabled' => false,
                        'config' => json_encode([]),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('platform_payment_settings');

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                foreach ([
                    'last_grace_notice_sent_at',
                    'last_renewal_notice_sent_at',
                    'locked_at',
                    'grace_ends_at',
                ] as $column) {
                    if (Schema::hasColumn('subscriptions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
