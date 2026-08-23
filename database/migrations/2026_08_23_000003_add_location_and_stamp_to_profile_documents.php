<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('company_settings', 'location')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->string('location')->nullable();
            });
        }

        if (Schema::hasTable('signatories') && ! Schema::hasColumn('signatories', 'stamp_path')) {
            Schema::table('signatories', function (Blueprint $table) {
                $table->string('stamp_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('signatories') && Schema::hasColumn('signatories', 'stamp_path')) {
            Schema::table('signatories', function (Blueprint $table) {
                $table->dropColumn('stamp_path');
            });
        }

        if (Schema::hasColumn('company_settings', 'location')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
