<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estate_tenants', function (Blueprint $table) {
            $table->boolean('billing_alert_enabled')->default(false)->after('status');
            $table->string('billing_alert_frequency')->default('Monthly')->after('billing_alert_enabled');
            $table->unsignedTinyInteger('billing_alert_day')->default(1)->after('billing_alert_frequency');
            $table->string('billing_alert_subject')->nullable()->after('billing_alert_day');
            $table->timestamp('last_billing_alert_sent_at')->nullable()->after('billing_alert_subject');
            $table->index(['business_id', 'billing_alert_enabled', 'billing_alert_frequency'], 're_tenant_billing_alert_idx');
        });
    }

    public function down(): void
    {
        Schema::table('real_estate_tenants', function (Blueprint $table) {
            $table->dropIndex('re_tenant_billing_alert_idx');
            $table->dropColumn([
                'billing_alert_enabled',
                'billing_alert_frequency',
                'billing_alert_day',
                'billing_alert_subject',
                'last_billing_alert_sent_at',
            ]);
        });
    }
};
