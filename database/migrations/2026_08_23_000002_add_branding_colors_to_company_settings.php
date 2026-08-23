<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('company_settings', 'primary_color')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->string('primary_color', 7)->default('#00A651');
            });
        }

        if (! Schema::hasColumn('company_settings', 'secondary_color')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->string('secondary_color', 7)->default('#111827');
            });
        }

        if (! Schema::hasColumn('company_settings', 'accent_color')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->string('accent_color', 7)->default('#E7F8EF');
            });
        }
    }

    public function down(): void
    {
        $columns = array_filter([
            Schema::hasColumn('company_settings', 'accent_color') ? 'accent_color' : null,
            Schema::hasColumn('company_settings', 'secondary_color') ? 'secondary_color' : null,
            Schema::hasColumn('company_settings', 'primary_color') ? 'primary_color' : null,
        ]);

        if ($columns !== []) {
            Schema::table('company_settings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
