<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_payments')) {
            return;
        }

        Schema::table('subscription_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_payments', 'merchant_reference')) {
                $table->string('merchant_reference')->nullable()->unique();
            }

            if (! Schema::hasColumn('subscription_payments', 'request_payload')) {
                $table->json('request_payload')->nullable();
            }

            if (! Schema::hasColumn('subscription_payments', 'response_payload')) {
                $table->json('response_payload')->nullable();
            }

            if (! Schema::hasColumn('subscription_payments', 'failure_code')) {
                $table->string('failure_code')->nullable();
            }

            if (! Schema::hasColumn('subscription_payments', 'failure_message')) {
                $table->text('failure_message')->nullable();
            }

            foreach (['initiated_at', 'completed_at', 'failed_at', 'cancelled_at', 'refunded_at', 'processed_at'] as $column) {
                if (! Schema::hasColumn('subscription_payments', $column)) {
                    $table->timestamp($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        // This migration repairs schema drift on existing installations. Its
        // columns may predate this migration, so rolling back must preserve them.
    }
};
