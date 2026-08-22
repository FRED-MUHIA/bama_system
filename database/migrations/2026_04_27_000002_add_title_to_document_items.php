<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('title')->nullable()->after('quotation_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('title')->nullable()->after('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
