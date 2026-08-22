<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIfMissing('automotive_fleets', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fleet_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fleet_number')->unique();
            $table->string('name');
            $table->json('service_rules')->nullable();
            $table->string('credit_terms')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'status']);
        });

        $this->createIfMissing('automotive_vehicles', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fleet_id')->nullable()->constrained('automotive_fleets')->nullOnDelete();
            $table->string('registration_number');
            $table->string('vin')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('make')->nullable()->index();
            $table->string('model')->nullable()->index();
            $table->string('variant')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->string('engine_capacity')->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('inspection_expiry')->nullable()->index();
            $table->unsignedInteger('service_interval')->nullable();
            $table->unsignedInteger('last_service_mileage')->nullable();
            $table->unsignedInteger('next_service_mileage')->nullable()->index();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable()->index();
            $table->string('status')->default('Active')->index();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'registration_number'], 'auto_vehicle_tenant_reg_unique');
            $table->unique(['tenant_id', 'vin'], 'auto_vehicle_tenant_vin_unique');
            $table->index(['tenant_id', 'business_id', 'client_id']);
        });

        $this->createIfMissing('automotive_service_bookings', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('automotive_vehicles')->nullOnDelete();
            $table->foreignId('service_advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('booking_number')->unique();
            $table->string('requested_service')->nullable();
            $table->date('preferred_date')->nullable()->index();
            $table->time('preferred_time')->nullable();
            $table->text('customer_complaint')->nullable();
            $table->boolean('pickup_required')->default(false);
            $table->boolean('dropoff_required')->default(false);
            $table->string('status')->default('Pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'preferred_date', 'status']);
        });

        $this->createIfMissing('automotive_workshop_bays', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('assigned_job_card_id')->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('status')->default('Available')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'business_id', 'name'], 'auto_bay_business_name_unique');
        });

        $this->createIfMissing('automotive_check_ins', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('booking_id')->nullable()->constrained('automotive_service_bookings')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('service_advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('check_in_number')->unique();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('fuel_level')->nullable();
            $table->timestamp('checked_in_at')->nullable()->index();
            $table->text('customer_complaint')->nullable();
            $table->json('existing_damage')->nullable();
            $table->json('accessories')->nullable();
            $table->json('warning_lights')->nullable();
            $table->json('photos')->nullable();
            $table->boolean('keys_received')->default(false);
            $table->timestamp('expected_completion')->nullable()->index();
            $table->string('customer_authorization')->nullable();
            $table->string('status')->default('Checked In')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'vehicle_id', 'checked_in_at']);
        });

        $this->createIfMissing('automotive_inspections', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('check_in_id')->nullable()->constrained('automotive_check_ins')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_number')->unique();
            $table->date('inspection_date')->nullable()->index();
            $table->string('status')->default('Draft')->index();
            $table->text('recommendations')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->json('photos')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'vehicle_id', 'status']);
        });

        $this->createIfMissing('automotive_inspection_items', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('inspection_id')->constrained('automotive_inspections')->cascadeOnDelete();
            $table->string('section');
            $table->string('item');
            $table->string('result')->default('Not Checked')->index();
            $table->text('notes')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->json('photos')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'inspection_id']);
        });

        $this->createIfMissing('automotive_job_cards', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('automotive_service_bookings')->nullOnDelete();
            $table->foreignId('check_in_id')->nullable()->constrained('automotive_check_ins')->nullOnDelete();
            $table->foreignId('service_advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('workshop_bay_id')->nullable()->constrained('automotive_workshop_bays')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_number')->unique();
            $table->unsignedInteger('mileage')->nullable();
            $table->text('customer_complaint')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('work_requested')->nullable();
            $table->timestamp('estimated_completion')->nullable()->index();
            $table->string('priority')->default('Normal')->index();
            $table->string('status')->default('Draft')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'status']);
            $table->index(['tenant_id', 'vehicle_id']);
        });

        $this->createIfMissing('automotive_labour_operations', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('labour_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('standard_hours', 10, 2)->default(0);
            $table->decimal('hourly_rate', 15, 2)->default(0);
            $table->string('skill_required')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'labour_code'], 'auto_labour_tenant_code_unique');
        });

        $this->createIfMissing('automotive_labour_tasks', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('labour_operation_id')->nullable()->constrained('automotive_labour_operations')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->decimal('standard_hours', 10, 2)->default(0);
            $table->decimal('billable_hours', 10, 2)->default(0);
            $table->decimal('actual_hours', 10, 2)->default(0);
            $table->decimal('hourly_rate', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('Pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'job_card_id', 'technician_id']);
        });

        $this->createIfMissing('automotive_parts', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('part_number');
            $table->string('oem_number')->nullable();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('brand')->nullable();
            $table->json('vehicle_compatibility')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->decimal('reorder_level', 15, 3)->default(0);
            $table->string('bin_location')->nullable();
            $table->unsignedInteger('warranty_period_days')->nullable();
            $table->string('status')->default('Active')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'part_number'], 'auto_parts_tenant_part_unique');
            $table->index(['tenant_id', 'business_id', 'category']);
        });

        $this->createIfMissing('automotive_part_compatibilities', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('part_id')->constrained('automotive_parts')->cascadeOnDelete();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('engine')->nullable();
            $table->string('variant')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'part_id', 'make', 'model']);
        });

        $this->createIfMissing('automotive_part_requests', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_number')->unique();
            $table->string('status')->default('Requested')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'job_card_id', 'status']);
        });

        $this->createIfMissing('automotive_part_request_items', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('part_request_id')->constrained('automotive_part_requests')->cascadeOnDelete();
            $table->foreignId('part_id')->nullable()->constrained('automotive_parts')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('part_name');
            $table->decimal('requested_qty', 15, 3)->default(0);
            $table->decimal('approved_qty', 15, 3)->default(0);
            $table->decimal('issued_qty', 15, 3)->default(0);
            $table->decimal('returned_qty', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('status')->default('Available')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'part_id', 'status']);
        });

        $this->createIfMissing('automotive_estimates', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('job_card_id')->nullable()->constrained('automotive_job_cards')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('automotive_vehicles')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estimate_number')->unique();
            $table->string('status')->default('Draft')->index();
            $table->decimal('labour_total', 15, 2)->default(0);
            $table->decimal('parts_total', 15, 2)->default(0);
            $table->decimal('consumables_total', 15, 2)->default(0);
            $table->decimal('external_total', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('estimated_hours', 10, 2)->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'job_card_id', 'status']);
        });

        $this->createIfMissing('automotive_estimate_items', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('estimate_id')->constrained('automotive_estimates')->cascadeOnDelete();
            $table->foreignId('part_id')->nullable()->constrained('automotive_parts')->nullOnDelete();
            $table->foreignId('labour_operation_id')->nullable()->constrained('automotive_labour_operations')->nullOnDelete();
            $table->string('type')->default('Labour')->index();
            $table->string('category')->default('Required Repairs')->index();
            $table->text('description');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->string('approval_status')->default('Pending')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'estimate_id', 'approval_status']);
        });

        $this->createIfMissing('automotive_diagnostics', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('diagnostic_number')->unique();
            $table->string('diagnostic_type')->nullable();
            $table->json('fault_codes')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('recommended_repair')->nullable();
            $table->date('diagnostic_date')->nullable()->index();
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_quality_checks', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qc_number')->unique();
            $table->json('checklist')->nullable();
            $table->string('result')->default('Pass')->index();
            $table->text('failure_reason')->nullable();
            $table->text('corrective_action')->nullable();
            $table->timestamp('inspected_at')->nullable()->index();
            $table->timestamps();
            $table->index(['tenant_id', 'job_card_id', 'result']);
        });

        $this->createIfMissing('automotive_road_tests', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('tester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('road_test_number')->unique();
            $table->unsignedInteger('start_mileage')->nullable();
            $table->unsignedInteger('end_mileage')->nullable();
            $table->decimal('distance', 10, 2)->default(0);
            $table->string('test_result')->default('Not Required')->index();
            $table->json('observations')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_vehicle_releases', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('release_number')->unique();
            $table->unsignedInteger('final_mileage')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamp('released_at')->nullable()->index();
            $table->string('customer_acknowledgement')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_job_costs', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->decimal('parts_cost', 15, 2)->default(0);
            $table->decimal('labour_cost', 15, 2)->default(0);
            $table->decimal('technician_cost', 15, 2)->default(0);
            $table->decimal('consumables_cost', 15, 2)->default(0);
            $table->decimal('outsourced_cost', 15, 2)->default(0);
            $table->decimal('transport_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('margin_percentage', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'job_card_id'], 'auto_job_cost_tenant_job_unique');
        });

        $this->createIfMissing('automotive_warranties', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_card_id')->nullable()->constrained('automotive_job_cards')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('automotive_vehicles')->nullOnDelete();
            $table->foreignId('part_id')->nullable()->constrained('automotive_parts')->nullOnDelete();
            $table->string('warranty_number')->unique();
            $table->string('type')->default('Parts Warranty')->index();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable()->index();
            $table->unsignedInteger('mileage_limit')->nullable();
            $table->text('terms')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_comebacks', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('original_job_card_id')->constrained('automotive_job_cards')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comeback_number')->unique();
            $table->text('complaint');
            $table->date('return_date')->nullable()->index();
            $table->text('cause')->nullable();
            $table->boolean('warranty')->default(false);
            $table->decimal('cost', 15, 2)->default(0);
            $table->text('resolution')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_service_reminders', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('vehicle_id')->constrained('automotive_vehicles')->cascadeOnDelete();
            $table->string('reminder_number')->unique();
            $table->string('type')->default('Service Due')->index();
            $table->date('due_date')->nullable()->index();
            $table->unsignedInteger('due_mileage')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_service_packages', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('package_code');
            $table->string('name');
            $table->json('labour')->nullable();
            $table->json('parts')->nullable();
            $table->json('fluids')->nullable();
            $table->decimal('standard_price', 15, 2)->default(0);
            $table->decimal('estimated_hours', 10, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'package_code'], 'auto_package_tenant_code_unique');
        });

        $this->createIfMissing('automotive_vehicle_sales', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stock_number')->unique();
            $table->string('vin')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->decimal('reconditioning_cost', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('status')->default('In Stock')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_test_drives', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_sale_id')->nullable()->constrained('automotive_vehicle_sales')->nullOnDelete();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('test_drive_number')->unique();
            $table->date('test_date')->nullable()->index();
            $table->string('driver_license')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('return_time')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('fuel')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_trade_ins', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_sale_id')->nullable()->constrained('automotive_vehicle_sales')->nullOnDelete();
            $table->string('trade_in_number')->unique();
            $table->string('registration_number')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->text('assessment')->nullable();
            $table->string('condition')->nullable();
            $table->decimal('trade_in_value', 15, 2)->default(0);
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->decimal('final_allowance', 15, 2)->default(0);
            $table->timestamps();
        });

        $this->createIfMissing('automotive_specialty_records', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('vehicle_id')->nullable()->constrained('automotive_vehicles')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('automotive_job_cards')->nullOnDelete();
            $table->string('record_number')->unique();
            $table->string('type')->index();
            $table->json('payload')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'type', 'status']);
        });

        $this->createIfMissing('automotive_customer_feedback', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('automotive_vehicles')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('automotive_job_cards')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('scores')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('automotive_complaints', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('automotive_vehicles')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('automotive_job_cards')->nullOnDelete();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('complaint_number')->unique();
            $table->string('category')->nullable();
            $table->text('description');
            $table->string('priority')->default('Normal')->index();
            $table->text('resolution')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();
        });

        $this->registerModule();
    }

    public function down(): void
    {
        foreach ([
            'automotive_complaints',
            'automotive_customer_feedback',
            'automotive_specialty_records',
            'automotive_trade_ins',
            'automotive_test_drives',
            'automotive_vehicle_sales',
            'automotive_service_packages',
            'automotive_service_reminders',
            'automotive_comebacks',
            'automotive_warranties',
            'automotive_job_costs',
            'automotive_vehicle_releases',
            'automotive_road_tests',
            'automotive_quality_checks',
            'automotive_diagnostics',
            'automotive_estimate_items',
            'automotive_estimates',
            'automotive_part_request_items',
            'automotive_part_requests',
            'automotive_part_compatibilities',
            'automotive_parts',
            'automotive_labour_tasks',
            'automotive_labour_operations',
            'automotive_job_cards',
            'automotive_inspection_items',
            'automotive_inspections',
            'automotive_check_ins',
            'automotive_workshop_bays',
            'automotive_service_bookings',
            'automotive_vehicles',
            'automotive_fleets',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('slug', 'automotive')->delete();
        }
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

    private function branch(Blueprint $table): void
    {
        if (Schema::hasTable('branches')) {
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
        } else {
            $table->unsignedBigInteger('branch_id')->nullable();
        }
    }

    private function registerModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $definition = require base_path('Modules/Automotive/module.php');

        DB::table('modules')->updateOrInsert(
            ['slug' => 'automotive'],
            [
                'name' => 'Automotive',
                'namespace' => 'Modules\\Automotive',
                'type' => 'industry',
                'industry' => 'automotive',
                'icon' => 'bi-car-front',
                'route' => 'automotive.dashboard',
                'permissions' => json_encode($definition['permissions'] ?? ['automotive.view']),
                'menu' => json_encode(['label' => 'Automotive', 'route' => 'automotive.dashboard']),
                'widgets' => json_encode($definition['widgets'] ?? ['automotive-overview']),
                'is_core' => false,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $moduleId = DB::table('modules')->where('slug', 'automotive')->value('id');

        if ($moduleId && Schema::hasTable('industry_modules')) {
            DB::table('industry_modules')->updateOrInsert(
                ['industry' => 'automotive', 'module_id' => $moduleId],
                ['enabled_by_default' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        if ($moduleId && Schema::hasTable('tenants') && Schema::hasTable('tenant_modules')) {
            DB::table('tenants')
                ->where('industry', 'automotive')
                ->pluck('id')
                ->each(function ($tenantId) use ($moduleId) {
                    DB::table('tenant_modules')->updateOrInsert(
                        ['tenant_id' => $tenantId, 'module_id' => $moduleId],
                        ['enabled' => true, 'enabled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
                    );
                });
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['automotive-overview', 'Automotive Operations Overview'],
                ['automotive-workshop-board', 'Automotive Workshop Board'],
                ['automotive-revenue', 'Automotive Revenue'],
                ['automotive-parts-alerts', 'Automotive Parts Alerts'],
                ['automotive-quality', 'Automotive Quality'],
            ] as [$slug, $name]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'automotive',
                        'industry' => 'automotive',
                        'component' => 'widgets.'.$slug,
                        'permission' => 'automotive.dashboard',
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
};
