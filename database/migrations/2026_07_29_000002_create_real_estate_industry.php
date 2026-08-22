<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('property_code');
            $table->string('property_name');
            $table->string('property_type');
            $table->string('ownership_type')->default('Owned');
            $table->string('status')->default('Available');
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('country')->nullable();
            $table->string('county_state')->nullable();
            $table->string('city')->nullable();
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->default(0);
            $table->decimal('market_value', 15, 2)->default(0);
            $table->json('images')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'property_code'], 're_properties_code_unique');
        });

        Schema::create('real_estate_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->string('floor')->nullable();
            $table->string('block')->nullable();
            $table->string('unit_type')->nullable();
            $table->unsignedSmallInteger('bedrooms')->default(0);
            $table->unsignedSmallInteger('bathrooms')->default(0);
            $table->decimal('square_footage', 12, 2)->default(0);
            $table->string('occupancy_status')->default('Vacant');
            $table->decimal('rent_amount', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['real_estate_property_id', 'unit_number'], 're_units_property_number_unique');
        });

        Schema::create('real_estate_tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_number');
            $table->string('id_number')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('employer')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('occupation')->nullable();
            $table->string('status')->default('Prospect');
            $table->timestamps();
            $table->unique(['business_id', 'tenant_number'], 're_tenants_number_unique');
            $table->unique(['business_id', 'client_id'], 're_tenants_client_unique');
        });

        Schema::create('real_estate_buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('buyer_number');
            $table->decimal('budget', 15, 2)->default(0);
            $table->text('preferred_locations')->nullable();
            $table->text('property_interests')->nullable();
            $table->string('status')->default('Prospect');
            $table->timestamps();
            $table->unique(['business_id', 'buyer_number'], 're_buyers_number_unique');
            $table->unique(['business_id', 'client_id'], 're_buyers_client_unique');
        });

        Schema::create('real_estate_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent_number');
            $table->string('name');
            $table->string('license_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->unique(['business_id', 'agent_number'], 're_agents_number_unique');
        });

        Schema::create('real_estate_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('listing_number');
            $table->string('listing_type');
            $table->decimal('price', 15, 2)->default(0);
            $table->date('listing_date');
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('public_ready')->default(false);
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('inquiries_count')->default(0);
            $table->unsignedInteger('leads_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->timestamps();
            $table->unique(['business_id', 'listing_number'], 're_listings_number_unique');
        });

        Schema::create('real_estate_leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lease_number');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('rent_amount', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->string('billing_cycle')->default('Monthly');
            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->decimal('rent_escalation_percent', 8, 2)->default(0);
            $table->date('next_bill_date')->nullable();
            $table->string('status')->default('Draft');
            $table->boolean('auto_billing')->default(false);
            $table->timestamps();
            $table->unique(['business_id', 'lease_number'], 're_leases_number_unique');
        });

        Schema::create('real_estate_rental_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('charge_number');
            $table->string('charge_type');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->string('status')->default('Outstanding');
            $table->timestamps();
            $table->unique(['business_id', 'charge_number'], 're_rental_charges_number_unique');
        });

        Schema::create('real_estate_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_buyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sale_number');
            $table->decimal('sale_price', 15, 2);
            $table->decimal('deposit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->date('completion_date')->nullable();
            $table->string('status')->default('Reserved');
            $table->timestamps();
            $table->unique(['business_id', 'sale_number'], 're_sales_number_unique');
        });

        Schema::create('real_estate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_agent_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('commissionable', 're_commissionable_idx');
            $table->string('commission_number');
            $table->string('commission_type');
            $table->string('calculation_type')->default('Percentage');
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('earned_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('Earned');
            $table->date('earned_date')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'commission_number'], 're_commissions_number_unique');
        });

        Schema::create('real_estate_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_number');
            $table->string('inspection_type');
            $table->date('inspection_date');
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
            $table->unique(['business_id', 'inspection_number'], 're_inspections_number_unique');
        });

        Schema::create('real_estate_maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_number');
            $table->string('maintenance_type')->default('Corrective');
            $table->string('category')->nullable();
            $table->string('priority')->default('Medium');
            $table->text('description');
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->date('scheduled_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
            $table->unique(['business_id', 'request_number'], 're_maintenance_number_unique');
        });

        Schema::create('real_estate_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_number');
            $table->string('request_type');
            $table->text('description');
            $table->string('status')->default('Open');
            $table->unsignedInteger('resolution_minutes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'request_number'], 're_service_requests_number_unique');
        });

        Schema::create('real_estate_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('valuer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('valuation_date');
            $table->decimal('market_value', 15, 2);
            $table->decimal('rental_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
        });

        Schema::create('real_estate_land_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parcel_number');
            $table->string('title_number')->nullable();
            $table->decimal('land_size', 15, 4)->default(0);
            $table->string('land_size_unit')->default('Acres');
            $table->string('zoning')->nullable();
            $table->string('ownership_status')->default('Owned');
            $table->json('ownership_history')->nullable();
            $table->json('sales_history')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'parcel_number'], 're_land_parcels_number_unique');
        });

        Schema::create('real_estate_development_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('real_estate_property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('development_number');
            $table->string('name');
            $table->string('phase')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('contractor')->nullable();
            $table->string('status')->default('Planning');
            $table->timestamps();
            $table->unique(['business_id', 'development_number'], 're_developments_number_unique');
        });

        Schema::create('real_estate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('documentable', 're_documentable_idx');
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        $this->registerModule();
    }

    public function down(): void
    {
        foreach ([
            'real_estate_documents',
            'real_estate_development_projects',
            'real_estate_land_parcels',
            'real_estate_valuations',
            'real_estate_service_requests',
            'real_estate_maintenance_requests',
            'real_estate_inspections',
            'real_estate_commissions',
            'real_estate_sales',
            'real_estate_rental_charges',
            'real_estate_leases',
            'real_estate_listings',
            'real_estate_agents',
            'real_estate_buyers',
            'real_estate_tenants',
            'real_estate_units',
            'real_estate_properties',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function registerModule(): void
    {
        if (Schema::hasTable('modules')) {
            DB::table('modules')->updateOrInsert(
                ['slug' => 'real-estate'],
                [
                    'name' => 'Real Estate',
                    'namespace' => 'Modules\\RealEstate',
                    'type' => 'industry',
                    'industry' => 'Real Estate',
                    'icon' => 'bi-house-door',
                    'route' => 'real-estate.dashboard',
                    'is_core' => false,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
};
