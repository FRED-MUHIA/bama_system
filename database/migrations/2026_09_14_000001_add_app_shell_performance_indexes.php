<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (! Schema::hasIndex('notifications', 'notifications_user_business_status_created_idx')) {
                    $table->index(['user_id', 'business_id', 'status', 'created_at'], 'notifications_user_business_status_created_idx');
                }

                if (! Schema::hasIndex('notifications', 'notifications_user_business_status_type_idx')) {
                    $table->index(['user_id', 'business_id', 'status', 'notification_type'], 'notifications_user_business_status_type_idx');
                }
            });
        }

        if (Schema::hasTable('business_user')) {
            Schema::table('business_user', function (Blueprint $table) {
                if (! Schema::hasIndex('business_user', 'business_user_user_status_business_idx')) {
                    $table->index(['user_id', 'status', 'business_id'], 'business_user_user_status_business_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasIndex('notifications', 'notifications_user_business_status_type_idx')) {
                    $table->dropIndex('notifications_user_business_status_type_idx');
                }

                if (Schema::hasIndex('notifications', 'notifications_user_business_status_created_idx')) {
                    $table->dropIndex('notifications_user_business_status_created_idx');
                }
            });
        }

        if (Schema::hasTable('business_user')) {
            Schema::table('business_user', function (Blueprint $table) {
                if (Schema::hasIndex('business_user', 'business_user_user_status_business_idx')) {
                    $table->dropIndex('business_user_user_status_business_idx');
                }
            });
        }
    }
};
