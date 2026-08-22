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
        $this->createOperationalTables();
        $this->registerModule();
    }

    public function down(): void
    {
        foreach ([
            'agriculture_documents',
            'agriculture_budget_lines',
            'agriculture_compliance_records',
            'agriculture_produce_sales',
            'agriculture_warehouse_movements',
            'agriculture_storage_bins',
            'agriculture_produce_warehouses',
            'agriculture_farmer_contracts',
            'agriculture_farmers',
            'agriculture_equipment_maintenance',
            'agriculture_equipment',
            'agriculture_irrigation_schedules',
            'agriculture_pest_disease_incidents',
            'agriculture_fertilizer_applications',
            'agriculture_input_usages',
            'agriculture_inputs',
            'agriculture_feed_usages',
            'agriculture_feed_types',
            'agriculture_livestock_productions',
            'agriculture_breeding_events',
            'agriculture_veterinary_records',
            'agriculture_animals',
            'agriculture_herds',
            'agriculture_produce_batches',
            'agriculture_harvests',
            'agriculture_farm_activities',
            'agriculture_crop_plans',
            'agriculture_crops',
            'agriculture_farm_workers',
            'agriculture_weather_records',
            'agriculture_farm_seasons',
            'agriculture_plots',
            'agriculture_fields',
            'agriculture_farm_zones',
            'agriculture_farm_branches',
            'agriculture_farms',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createOperationalTables(): void
    {
        $this->createIfMissing('agriculture_farms', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('farm_code');
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('gps_coordinates')->nullable();
            $table->string('county_region')->nullable()->index();
            $table->decimal('total_area', 14, 4)->default(0);
            $table->string('measurement_unit')->default('Acres');
            $table->string('ownership_type')->default('Owned')->index();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'farm_code'], 'agri_farms_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_farm_branches', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('branch_code');
            $table->string('name');
            $table->string('location')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'branch_code'], 'agri_branches_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_farm_zones', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('agriculture_farm_branch_id')->nullable()->constrained('agriculture_farm_branches')->nullOnDelete();
            $table->string('zone_code');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'zone_code'], 'agri_zones_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_fields', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('agriculture_farm_branch_id')->nullable()->constrained('agriculture_farm_branches')->nullOnDelete();
            $table->foreignId('agriculture_farm_zone_id')->nullable()->constrained('agriculture_farm_zones')->nullOnDelete();
            $table->string('field_code');
            $table->string('name');
            $table->decimal('size', 14, 4)->default(0);
            $table->string('measurement_unit')->default('Acres');
            $table->string('soil_type')->nullable();
            $table->string('irrigation_type')->default('Rain-fed')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('gps_location')->nullable();
            $table->string('current_crop')->nullable();
            $table->string('previous_crop')->nullable();
            $table->string('status')->default('Available')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'field_code'], 'agri_fields_tenant_code_unique');
            $table->index(['tenant_id', 'farm_id', 'status'], 'agri_fields_tenant_farm_status_idx');
        });

        $this->createIfMissing('agriculture_plots', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->constrained('agriculture_fields')->cascadeOnDelete();
            $table->string('plot_code');
            $table->string('name');
            $table->decimal('size', 14, 4)->default(0);
            $table->string('soil_type')->nullable();
            $table->string('status')->default('Available')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'plot_code'], 'agri_plots_tenant_code_unique');
            $table->index(['tenant_id', 'field_id'], 'agri_plots_tenant_field_idx');
        });

        $this->createIfMissing('agriculture_farm_seasons', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->string('season_code');
            $table->string('name');
            $table->date('starts_at')->index();
            $table->date('ends_at')->nullable()->index();
            $table->string('rainfall_expectation')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'season_code'], 'agri_seasons_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_weather_records', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->date('recorded_on')->index();
            $table->decimal('rainfall_mm', 10, 2)->default(0);
            $table->decimal('temperature_c', 6, 2)->nullable();
            $table->decimal('humidity_percent', 6, 2)->nullable();
            $table->decimal('wind_kph', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'farm_id', 'recorded_on'], 'agri_weather_tenant_farm_date_idx');
        });

        $this->createIfMissing('agriculture_farm_workers', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('agriculture_fields')->nullOnDelete();
            $table->string('worker_number');
            $table->string('name');
            $table->string('role_title')->default('Field Worker')->index();
            $table->json('duties')->nullable();
            $table->json('work_schedule')->nullable();
            $table->unsignedInteger('activities_completed')->default(0);
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'worker_number'], 'agri_workers_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_crops', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('crop_code');
            $table->string('name');
            $table->string('category')->index();
            $table->string('variety')->nullable();
            $table->unsignedInteger('expected_growing_period_days')->default(0);
            $table->string('recommended_planting_season')->nullable();
            $table->decimal('expected_yield', 14, 3)->default(0);
            $table->string('yield_unit')->default('kg');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'crop_code'], 'agri_crops_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_crop_plans', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->constrained('agriculture_fields')->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('agriculture_farm_seasons')->nullOnDelete();
            $table->foreignId('crop_id')->constrained('agriculture_crops')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan_number');
            $table->string('variety')->nullable();
            $table->date('planting_date')->nullable()->index();
            $table->date('expected_germination_date')->nullable()->index();
            $table->date('expected_harvest_date')->nullable()->index();
            $table->decimal('planned_acreage', 14, 4)->default(0);
            $table->decimal('seed_quantity', 14, 3)->default(0);
            $table->decimal('expected_yield', 14, 3)->default(0);
            $table->decimal('budget', 14, 2)->default(0);
            $table->string('status')->default('Draft')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'plan_number'], 'agri_crop_plans_tenant_number_unique');
            $table->index(['tenant_id', 'farm_id', 'field_id', 'crop_id'], 'agri_crop_plan_trace_idx');
        });

        $this->createIfMissing('agriculture_farm_activities', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->nullable()->constrained('agriculture_fields')->nullOnDelete();
            $table->foreignId('crop_plan_id')->nullable()->constrained('agriculture_crop_plans')->nullOnDelete();
            $table->foreignId('assigned_worker_id')->nullable()->constrained('agriculture_farm_workers')->nullOnDelete();
            $table->unsignedBigInteger('equipment_id')->nullable()->index();
            $table->string('activity_number');
            $table->string('activity_type')->index();
            $table->date('scheduled_date')->index();
            $table->date('completion_date')->nullable()->index();
            $table->json('inputs_used')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('Planned')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'activity_number'], 'agri_activities_tenant_number_unique');
            $table->index(['tenant_id', 'crop_plan_id', 'scheduled_date'], 'agri_activities_plan_date_idx');
        });

        $this->createIfMissing('agriculture_harvests', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->constrained('agriculture_fields')->cascadeOnDelete();
            $table->foreignId('crop_plan_id')->nullable()->constrained('agriculture_crop_plans')->nullOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained('agriculture_crops')->nullOnDelete();
            $table->string('harvest_number');
            $table->date('harvest_date')->index();
            $table->decimal('quantity', 14, 3);
            $table->string('measurement_unit')->default('kg');
            $table->string('grade')->default('Grade A')->index();
            $table->string('quality')->nullable();
            $table->decimal('waste_quantity', 14, 3)->default(0);
            $table->string('destination')->default('Storage')->index();
            $table->string('storage_location')->nullable();
            $table->decimal('expected_yield', 14, 3)->default(0);
            $table->decimal('value', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'harvest_number'], 'agri_harvests_tenant_number_unique');
            $table->index(['tenant_id', 'field_id', 'crop_plan_id', 'harvest_date'], 'agri_harvest_trace_idx');
        });

        $this->createIfMissing('agriculture_produce_batches', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('harvest_id')->nullable()->constrained('agriculture_harvests')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number');
            $table->string('traceability_id')->index();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->string('measurement_unit')->default('kg');
            $table->string('grade')->default('Grade A')->index();
            $table->string('storage_location')->nullable();
            $table->date('date_received')->index();
            $table->date('recommended_sale_date')->nullable()->index();
            $table->string('quality_status')->default('Good')->index();
            $table->string('stage')->default('Storage')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'batch_number'], 'agri_batches_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_herds', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->string('herd_code');
            $table->string('name');
            $table->string('category')->index();
            $table->string('breed')->nullable();
            $table->unsignedInteger('animal_count')->default(0);
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'herd_code'], 'agri_herds_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_animals', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('herd_id')->nullable()->constrained('agriculture_herds')->nullOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->foreignId('father_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->string('animal_id');
            $table->string('tag_number')->nullable();
            $table->string('name')->nullable();
            $table->string('species')->index();
            $table->string('breed')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable()->index();
            $table->date('acquisition_date')->nullable()->index();
            $table->decimal('weight', 10, 3)->default(0);
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'animal_id'], 'agri_animals_tenant_id_unique');
            $table->unique(['tenant_id', 'tag_number'], 'agri_animals_tenant_tag_unique');
            $table->index(['tenant_id', 'farm_id', 'herd_id'], 'agri_animals_farm_herd_idx');
        });

        $this->createIfMissing('agriculture_veterinary_records', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('animal_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->foreignId('herd_id')->nullable()->constrained('agriculture_herds')->nullOnDelete();
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_number');
            $table->string('record_type')->index();
            $table->date('record_date')->index();
            $table->string('diagnosis')->nullable();
            $table->string('medication')->nullable();
            $table->decimal('treatment_cost', 14, 2)->default(0);
            $table->date('next_due_date')->nullable()->index();
            $table->string('recovery_status')->default('Monitoring')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'record_number'], 'agri_vet_records_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_breeding_events', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('animal_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->foreignId('herd_id')->nullable()->constrained('agriculture_herds')->nullOnDelete();
            $table->string('event_number');
            $table->string('method')->index();
            $table->date('event_date')->index();
            $table->date('pregnancy_check_date')->nullable()->index();
            $table->date('expected_birth_date')->nullable()->index();
            $table->date('birth_date')->nullable()->index();
            $table->unsignedInteger('offspring_count')->default(0);
            $table->string('status')->default('Pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'event_number'], 'agri_breeding_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_livestock_productions', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('animal_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->foreignId('herd_id')->nullable()->constrained('agriculture_herds')->nullOnDelete();
            $table->string('production_number');
            $table->string('production_type')->index();
            $table->date('production_date')->index();
            $table->decimal('morning_quantity', 14, 3)->default(0);
            $table->decimal('evening_quantity', 14, 3)->default(0);
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('damaged_quantity', 14, 3)->default(0);
            $table->decimal('sold_quantity', 14, 3)->default(0);
            $table->string('measurement_unit')->default('litres');
            $table->decimal('value', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'production_number'], 'agri_production_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_feed_types', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feed_code');
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('unit')->default('kg');
            $table->decimal('cost_per_unit', 14, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'feed_code'], 'agri_feed_types_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_feed_usages', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('feed_type_id')->constrained('agriculture_feed_types')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->foreignId('herd_id')->nullable()->constrained('agriculture_herds')->nullOnDelete();
            $table->date('usage_date')->index();
            $table->decimal('quantity', 14, 3);
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('allocation_target')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('agriculture_inputs', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('input_code');
            $table->string('name');
            $table->string('category')->index();
            $table->string('batch_number')->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('application_rate', 14, 3)->default(0);
            $table->string('unit')->default('kg');
            $table->unsignedInteger('safety_period_days')->default(0);
            $table->string('storage_conditions')->nullable();
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'input_code'], 'agri_inputs_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_input_usages', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('input_id')->constrained('agriculture_inputs')->cascadeOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('agriculture_fields')->nullOnDelete();
            $table->foreignId('crop_plan_id')->nullable()->constrained('agriculture_crop_plans')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('agriculture_farm_activities')->nullOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('agriculture_farm_workers')->nullOnDelete();
            $table->string('usage_number');
            $table->date('usage_date')->index();
            $table->decimal('quantity_used', 14, 3);
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('status')->default('Issued')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'usage_number'], 'agri_input_usage_tenant_number_unique');
            $table->index(['tenant_id', 'farm_id', 'field_id', 'crop_plan_id'], 'agri_input_usage_trace_idx');
        });

        $this->createIfMissing('agriculture_fertilizer_applications', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->constrained('agriculture_fields')->cascadeOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained('agriculture_crops')->nullOnDelete();
            $table->string('fertilizer_type');
            $table->decimal('application_rate', 14, 3)->default(0);
            $table->date('application_date')->index();
            $table->decimal('quantity', 14, 3);
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('application_method')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('agriculture_pest_disease_incidents', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->nullable()->constrained('agriculture_fields')->nullOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained('agriculture_crops')->nullOnDelete();
            $table->string('incident_number');
            $table->string('name');
            $table->string('severity')->default('Low')->index();
            $table->date('observation_date')->index();
            $table->json('photos')->nullable();
            $table->text('recommended_action')->nullable();
            $table->string('chemical_used')->nullable();
            $table->text('treatment')->nullable();
            $table->date('follow_up_date')->nullable()->index();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'incident_number'], 'agri_incidents_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_irrigation_schedules', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->constrained('agriculture_fields')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('schedule_number');
            $table->string('irrigation_type')->default('Drip')->index();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->decimal('water_quantity', 14, 3)->default(0);
            $table->string('pump')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('iot_reference')->nullable();
            $table->string('status')->default('Scheduled')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'schedule_number'], 'agri_irrigation_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_equipment', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('assigned_operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('fixed_asset_id')->nullable()->constrained('fixed_assets')->nullOnDelete();
            $table->string('equipment_code');
            $table->string('name');
            $table->string('equipment_type')->index();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable()->index();
            $table->decimal('purchase_cost', 14, 2)->default(0);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->string('fuel_type')->nullable();
            $table->string('status')->default('Available')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'equipment_code'], 'agri_equipment_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_equipment_maintenance', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('equipment_id')->constrained('agriculture_equipment')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('maintenance_number');
            $table->date('service_date')->index();
            $table->string('service_type')->index();
            $table->json('parts_used')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->date('next_service_date')->nullable()->index();
            $table->decimal('meter_hours_reading', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('Completed')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'maintenance_number'], 'agri_equipment_maint_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_farmers', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('farmer_number');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->string('farm_location')->nullable();
            $table->decimal('acreage', 14, 4)->default(0);
            $table->json('crops')->nullable();
            $table->decimal('input_advances', 14, 2)->default(0);
            $table->decimal('deliveries_value', 14, 2)->default(0);
            $table->decimal('payments_value', 14, 2)->default(0);
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'farmer_number'], 'agri_farmers_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_farmer_contracts', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('farmer_id')->constrained('agriculture_farmers')->cascadeOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained('agriculture_crops')->nullOnDelete();
            $table->string('contract_number');
            $table->string('contracting_company')->nullable();
            $table->decimal('acreage', 14, 4)->default(0);
            $table->json('inputs_provided')->nullable();
            $table->decimal('expected_quantity', 14, 3)->default(0);
            $table->decimal('agreed_price', 14, 2)->default(0);
            $table->json('delivery_dates')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'contract_number'], 'agri_contracts_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_produce_warehouses', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->string('warehouse_code');
            $table->string('name');
            $table->string('warehouse_type')->default('Produce Warehouse')->index();
            $table->string('location')->nullable();
            $table->decimal('capacity', 14, 3)->default(0);
            $table->string('temperature_control')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'warehouse_code'], 'agri_warehouses_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_storage_bins', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('warehouse_id')->constrained('agriculture_produce_warehouses')->cascadeOnDelete();
            $table->string('bin_code');
            $table->string('name');
            $table->decimal('capacity', 14, 3)->default(0);
            $table->string('quality')->nullable();
            $table->decimal('temperature_c', 6, 2)->nullable();
            $table->string('status')->default('Available')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'bin_code'], 'agri_bins_tenant_code_unique');
        });

        $this->createIfMissing('agriculture_warehouse_movements', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('produce_batch_id')->constrained('agriculture_produce_batches')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('agriculture_produce_warehouses')->nullOnDelete();
            $table->foreignId('storage_bin_id')->nullable()->constrained('agriculture_storage_bins')->nullOnDelete();
            $table->string('movement_number');
            $table->string('movement_type')->index();
            $table->date('movement_date')->index();
            $table->decimal('quantity', 14, 3);
            $table->decimal('loss_quantity', 14, 3)->default(0);
            $table->string('quality')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'movement_number'], 'agri_movements_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_produce_sales', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('produce_batch_id')->nullable()->constrained('agriculture_produce_batches')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sale_number');
            $table->string('buyer_type')->default('Wholesaler')->index();
            $table->date('sale_date')->index();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('delivery_status')->default('Pending')->index();
            $table->string('payment_status')->default('Unpaid')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'sale_number'], 'agri_sales_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_compliance_records', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->string('compliance_number');
            $table->string('compliance_type')->index();
            $table->string('certificate_number')->nullable()->index();
            $table->date('issue_date')->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'compliance_number'], 'agri_compliance_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_budget_lines', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->foreignId('field_id')->nullable()->constrained('agriculture_fields')->nullOnDelete();
            $table->foreignId('crop_plan_id')->nullable()->constrained('agriculture_crop_plans')->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('agriculture_animals')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('agriculture_equipment')->nullOnDelete();
            $table->string('budget_number');
            $table->string('budget_type')->index();
            $table->string('category')->index();
            $table->unsignedInteger('fiscal_year')->index();
            $table->decimal('budget_amount', 14, 2)->default(0);
            $table->decimal('actual_amount', 14, 2)->default(0);
            $table->decimal('alert_threshold', 5, 2)->default(90);
            $table->string('status')->default('Open')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'budget_number'], 'agri_budgets_tenant_number_unique');
        });

        $this->createIfMissing('agriculture_documents', function (Blueprint $table) {
            $this->tenantBusinessFarm($table);
            $table->nullableMorphs('documentable', 'agri_documentable_idx');
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
        });
    }

    private function createIfMissing(string $table, callable $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    private function tenantBusiness(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
        $table->index(['tenant_id', 'business_id']);
    }

    private function tenantBusinessFarm(Blueprint $table): void
    {
        $this->tenantBusiness($table);
        $table->foreignId('farm_id')->nullable()->constrained('agriculture_farms')->cascadeOnDelete();
        $table->index(['tenant_id', 'farm_id']);
    }

    private function registerModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $definition = require base_path('Modules/Agriculture/module.php');
        DB::table('modules')->updateOrInsert(
            ['slug' => 'agriculture'],
            [
                'name' => 'Agriculture',
                'namespace' => 'Modules\\Agriculture',
                'type' => 'industry',
                'industry' => 'agriculture',
                'icon' => 'bi-flower1',
                'route' => 'agriculture.dashboard',
                'permissions' => json_encode($definition['permissions'] ?? ['agriculture.view']),
                'menu' => json_encode(['label' => 'Agriculture', 'route' => 'agriculture.dashboard']),
                'widgets' => json_encode(['agriculture-overview', 'agriculture-risk-alerts']),
                'is_core' => false,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (Schema::hasTable('industry_modules')) {
            $moduleId = DB::table('modules')->where('slug', 'agriculture')->value('id');
            if ($moduleId) {
                DB::table('industry_modules')->updateOrInsert(
                    ['industry' => 'agriculture', 'module_id' => $moduleId],
                    ['enabled_by_default' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
};
