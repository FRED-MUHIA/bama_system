<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_settings')) {
            return;
        }

        $columns = [];
        if (! Schema::hasColumn('security_settings', 'pos_void_pin')) {
            $columns[] = 'pos_void_pin';
        }
        if (! Schema::hasColumn('security_settings', 'pos_edit_pin')) {
            $columns[] = 'pos_edit_pin';
        }

        if ($columns !== []) {
            Schema::table('security_settings', function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('security_settings')) {
            return;
        }

        $columns = array_values(array_filter(
            ['pos_void_pin', 'pos_edit_pin'],
            fn (string $column) => Schema::hasColumn('security_settings', $column)
        ));

        if ($columns !== []) {
            Schema::table('security_settings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
