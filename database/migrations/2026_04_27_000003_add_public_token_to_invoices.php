<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('invoice_number');
        });

        DB::table('invoices')->orderBy('id')->get(['id'])->each(function ($invoice) {
            DB::table('invoices')->where('id', $invoice->id)->update(['public_token' => Str::random(48)]);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
