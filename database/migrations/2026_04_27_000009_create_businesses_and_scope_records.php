<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $bamaId = DB::table('businesses')->insertGetId([
            'name' => 'BAMA',
            'slug' => 'bama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $interiorsId = DB::table('businesses')->insertGetId([
            'name' => 'BAMA INTERIORS',
            'slug' => 'bama-interiors',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tables = [
            'company_settings',
            'clients',
            'payment_methods',
            'terms_conditions',
            'quotations',
            'invoices',
            'payments',
            'receipts',
            'product_categories',
            'products',
            'pos_orders',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
            DB::table($table)->update(['business_id' => $bamaId]);
        }

        DB::table('company_settings')->insert([
            'business_id' => $interiorsId,
            'company_name' => 'BAMA INTERIORS',
            'phone' => '+254 700 000 000',
            'email' => 'admin@bamainteriors.co.ke',
            'tax_name' => 'VAT',
            'tax_rate' => 0,
            'currency_code' => 'KES',
            'locale' => 'en_KE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_methods')->insert([
            'business_id' => $interiorsId,
            'name' => 'Cash',
            'type' => 'cash',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        foreach (['pos_orders', 'products', 'product_categories', 'receipts', 'payments', 'invoices', 'quotations', 'terms_conditions', 'payment_methods', 'clients', 'company_settings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('business_id');
            });
        }

        Schema::dropIfExists('businesses');
    }
};
