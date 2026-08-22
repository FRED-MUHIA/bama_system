<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fitness_equipment') || Schema::hasColumn('fitness_equipment', 'cost')) {
            return;
        }

        Schema::table('fitness_equipment', function (Blueprint $table) {
            $table->decimal('cost', 14, 2)->default(0)->after('serial_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fitness_equipment') || ! Schema::hasColumn('fitness_equipment', 'cost')) {
            return;
        }

        Schema::table('fitness_equipment', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};
