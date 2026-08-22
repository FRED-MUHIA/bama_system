<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'industry_module')) {
                $table->string('industry_module')->nullable()->after('public_token');
            }

            if (! Schema::hasColumn('invoices', 'industry_reference')) {
                $table->string('industry_reference')->nullable()->after('industry_module');
            }

            if (! Schema::hasColumn('invoices', 'issuer_profile')) {
                $table->json('issuer_profile')->nullable()->after('industry_reference');
            }

            if (! Schema::hasColumn('invoices', 'recipient_profile')) {
                $table->json('recipient_profile')->nullable()->after('issuer_profile');
            }

            if (! Schema::hasColumn('invoices', 'industry_context')) {
                $table->json('industry_context')->nullable()->after('recipient_profile');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            foreach (['industry_context', 'recipient_profile', 'issuer_profile', 'industry_reference', 'industry_module'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
