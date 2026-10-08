<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_settings') && ! Schema::hasColumn('security_settings', 'pos_void_pin')) {
            Schema::table('security_settings', function (Blueprint $table) {
                $table->string('pos_void_pin')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('security_settings') && Schema::hasColumn('security_settings', 'pos_void_pin')) {
            Schema::table('security_settings', function (Blueprint $table) {
                $table->dropColumn('pos_void_pin');
            });
        }
    }
};
