<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('finance_accounts', 'description')) {
                $table->text('description')->nullable()->after('subtype');
            }

            if (! Schema::hasColumn('finance_accounts', 'tax_treatment')) {
                $table->string('tax_treatment')->nullable()->after('description');
            }

            if (! Schema::hasColumn('finance_accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('tax_treatment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            foreach (['opening_balance', 'tax_treatment', 'description'] as $column) {
                if (Schema::hasColumn('finance_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
