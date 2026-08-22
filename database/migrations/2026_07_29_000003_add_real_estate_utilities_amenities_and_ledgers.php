<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_utility_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('billing_method')->default('Metered');
            $table->decimal('default_rate', 15, 4)->default(0);
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'name'], 're_utility_types_name_unique');
        });

        Schema::create('real_estate_utility_meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_utility_type_id')->constrained('real_estate_utility_types', indexName: 're_meter_utility_type_fk')->cascadeOnDelete();
            $table->string('meter_number');
            $table->string('meter_type')->default('Custom Meter');
            $table->decimal('previous_reading', 15, 4)->default(0);
            $table->decimal('current_reading', 15, 4)->default(0);
            $table->date('reading_date')->nullable();
            $table->decimal('rate_per_unit', 15, 4)->default(0);
            $table->string('smart_meter_reference')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->unique(['business_id', 'meter_number'], 're_utility_meters_number_unique');
        });

        Schema::create('real_estate_meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_utility_meter_id')->constrained('real_estate_utility_meters', indexName: 're_reading_meter_fk')->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_utility_type_id')->constrained('real_estate_utility_types', indexName: 're_reading_utility_type_fk')->cascadeOnDelete();
            $table->decimal('previous_reading', 15, 4)->default(0);
            $table->decimal('current_reading', 15, 4)->default(0);
            $table->decimal('consumption', 15, 4)->default(0);
            $table->date('reading_date');
            $table->decimal('rate_per_unit', 15, 4)->default(0);
            $table->decimal('charge_amount', 15, 2)->default(0);
            $table->string('source')->default('Manual Entry');
            $table->string('status')->default('Pending Billing');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('real_estate_utility_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_utility_type_id')->constrained('real_estate_utility_types', indexName: 're_rate_utility_type_fk')->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->nullable()->constrained()->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('rate_per_unit', 15, 4)->default(0);
            $table->decimal('fixed_charge', 15, 2)->default(0);
            $table->decimal('minimum_charge', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('real_estate_utility_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_lease_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_utility_type_id')->constrained('real_estate_utility_types', indexName: 're_bill_utility_type_fk')->cascadeOnDelete();
            $table->foreignId('real_estate_meter_reading_id')->nullable()->constrained('real_estate_meter_readings', indexName: 're_bill_meter_reading_fk')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bill_number');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('rate_per_unit', 15, 4)->default(0);
            $table->decimal('fixed_charge', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('Outstanding');
            $table->timestamps();
            $table->unique(['business_id', 'bill_number'], 're_utility_bills_number_unique');
        });

        Schema::create('real_estate_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->text('booking_rules')->nullable();
            $table->string('fee_type')->default('Fixed');
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'name'], 're_amenities_name_unique');
        });

        Schema::create('real_estate_amenity_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_amenity_id')->constrained('real_estate_amenities', indexName: 're_booking_amenity_fk')->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('booking_number');
            $table->date('booking_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('charge_amount', 15, 2)->default(0);
            $table->string('status')->default('Pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'booking_number'], 're_amenity_bookings_number_unique');
        });

        Schema::create('real_estate_tenant_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_lease_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('ledgerable', 're_tenant_ledgerable_idx');
            $table->date('entry_date');
            $table->string('entry_type');
            $table->string('description');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('running_balance', 15, 2)->default(0);
            $table->string('status')->default('Posted');
            $table->timestamps();
        });

        Schema::create('real_estate_tenant_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_lease_id')->nullable()->constrained()->nullOnDelete();
            $table->string('statement_number');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->decimal('current_charges', 15, 2)->default(0);
            $table->decimal('payments_made', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->json('summary')->nullable();
            $table->string('status')->default('Generated');
            $table->timestamps();
            $table->unique(['business_id', 'statement_number'], 're_tenant_statements_number_unique');
        });

        Schema::create('real_estate_utility_consumption', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_utility_type_id')->constrained('real_estate_utility_types', indexName: 're_consumption_utility_type_fk')->cascadeOnDelete();
            $table->foreignId('real_estate_meter_reading_id')->nullable()->constrained('real_estate_meter_readings', indexName: 're_consumption_meter_reading_fk')->nullOnDelete();
            $table->date('consumption_date');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('real_estate_utility_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_statement_id')->nullable()->constrained('real_estate_tenant_statements', indexName: 're_utility_invoice_statement_fk')->nullOnDelete();
            $table->string('invoice_type')->default('Utility');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status')->default('Issued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'real_estate_utility_invoices',
            'real_estate_utility_consumption',
            'real_estate_tenant_statements',
            'real_estate_tenant_ledgers',
            'real_estate_amenity_bookings',
            'real_estate_amenities',
            'real_estate_utility_bills',
            'real_estate_utility_rates',
            'real_estate_meter_readings',
            'real_estate_utility_meters',
            'real_estate_utility_types',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
