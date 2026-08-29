<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_payments')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('subscription_payments', 'merchant_reference')) {
                    $table->string('merchant_reference')->nullable()->after('tenant_id')->unique();
                }
                if (! Schema::hasColumn('subscription_payments', 'request_payload')) {
                    $table->json('request_payload')->nullable()->after('payment_url');
                }
                if (! Schema::hasColumn('subscription_payments', 'response_payload')) {
                    $table->json('response_payload')->nullable()->after('request_payload');
                }
                if (! Schema::hasColumn('subscription_payments', 'failure_code')) {
                    $table->string('failure_code')->nullable()->after('callback_payload');
                }
                if (! Schema::hasColumn('subscription_payments', 'failure_message')) {
                    $table->text('failure_message')->nullable()->after('failure_code');
                }
                if (! Schema::hasColumn('subscription_payments', 'initiated_at')) {
                    $table->timestamp('initiated_at')->nullable()->after('paid_at');
                }
                if (! Schema::hasColumn('subscription_payments', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('initiated_at');
                }
                if (! Schema::hasColumn('subscription_payments', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable()->after('completed_at');
                }
                if (! Schema::hasColumn('subscription_payments', 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('failed_at');
                }
                if (! Schema::hasColumn('subscription_payments', 'refunded_at')) {
                    $table->timestamp('refunded_at')->nullable()->after('cancelled_at');
                }
                if (! Schema::hasColumn('subscription_payments', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable()->after('refunded_at');
                }
            });
        }

        if (! Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('gateway');
                $table->string('event_id')->nullable();
                $table->string('event_type')->nullable();
                $table->json('payload_json')->nullable();
                $table->boolean('signature_valid')->default(false);
                $table->boolean('processed')->default(false);
                $table->text('processing_error')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                $table->unique(['gateway', 'event_id']);
                $table->index(['gateway', 'processed']);
            });
        }

        if (! Schema::hasTable('payment_audit_logs')) {
            Schema::create('payment_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_payment_id')->nullable()->constrained('subscription_payments')->nullOnDelete();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event');
                $table->json('context_json')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'event']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audit_logs');
        Schema::dropIfExists('payment_webhook_events');
    }
};
