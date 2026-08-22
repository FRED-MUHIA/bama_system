<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_quotes')) {
            return;
        }

        Schema::table('supplier_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_quotes', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('supplier_quotes', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_quotes')) {
            return;
        }

        Schema::table('supplier_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_quotes', 'cost_center_id')) {
                $table->dropConstrainedForeignId('cost_center_id');
            }

            if (Schema::hasColumn('supplier_quotes', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
    }
};
