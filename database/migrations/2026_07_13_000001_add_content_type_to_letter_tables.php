<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['letters', 'letter_templates'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'content_type')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('content_type')->default('text')->after('content');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['letters', 'letter_templates'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'content_type')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('content_type');
                });
            }
        }
    }
};
