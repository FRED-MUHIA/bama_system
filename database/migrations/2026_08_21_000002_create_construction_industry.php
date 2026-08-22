<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_project_profiles', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('consultant_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('architect_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('engineer_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('quantity_surveyor_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('site_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('project_number')->unique();
            $table->string('contract_type')->nullable();
            $table->decimal('contract_value', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('planned_completion')->nullable();
            $table->date('actual_completion')->nullable();
            $table->string('location')->nullable();
            $table->decimal('retention_percentage', 8, 2)->default(0);
            $table->unsignedInteger('defects_liability_days')->default(0);
            $table->string('status')->default('Tendering');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'status']);
        });

        Schema::create('construction_sites', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $this->branch($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->date('start_date')->nullable();
            $table->string('operating_hours')->nullable();
            $table->string('status')->default('Active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_boqs', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $this->branch($table);
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('boq_number')->unique();
            $table->string('title');
            $table->string('type')->default('Tender BOQ');
            $table->unsignedInteger('revision')->default(1);
            $table->decimal('preliminaries', 15, 2)->default(0);
            $table->decimal('contingency', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_boq_sections', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('boq_id')->constrained('construction_boqs')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('construction_boq_sections')->nullOnDelete();
            $table->string('section_number')->nullable();
            $table->string('title');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'boq_id']);
        });

        Schema::create('construction_boq_items', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('boq_id')->constrained('construction_boqs')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('construction_boq_sections')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_number')->nullable();
            $table->text('description');
            $table->string('unit')->default('Item');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('material_rate', 15, 2)->default(0);
            $table->decimal('labour_rate', 15, 2)->default(0);
            $table->decimal('equipment_rate', 15, 2)->default(0);
            $table->decimal('subcontract_rate', 15, 2)->default(0);
            $table->decimal('unit_rate', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'boq_id']);
        });

        Schema::create('construction_rate_components', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('boq_item_id')->constrained('construction_boq_items')->cascadeOnDelete();
            $table->string('component_type');
            $table->string('name');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'boq_item_id', 'component_type']);
        });

        Schema::create('construction_estimates', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('boq_id')->nullable()->constrained('construction_boqs')->nullOnDelete();
            $table->foreignId('estimator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estimate_number')->unique();
            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            $table->decimal('direct_cost', 15, 2)->default(0);
            $table->decimal('overhead_percentage', 8, 2)->default(0);
            $table->decimal('profit_percentage', 8, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'status']);
        });

        Schema::create('construction_tenders', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $this->branch($table);
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('boq_id')->nullable()->constrained('construction_boqs')->nullOnDelete();
            $table->foreignId('estimate_id')->nullable()->constrained('construction_estimates')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tender_number')->unique();
            $table->string('name');
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->decimal('tender_value', 15, 2)->default(0);
            $table->date('submission_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->decimal('tender_bond', 15, 2)->default(0);
            $table->date('site_visit_date')->nullable();
            $table->string('status')->default('Identified');
            $table->text('requirements')->nullable();
            $table->json('documents')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'status', 'closing_date']);
        });

        Schema::create('construction_tender_checklist_items', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('tender_id')->constrained('construction_tenders')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('required')->default(true);
            $table->boolean('uploaded')->default(false);
            $table->boolean('verified')->default(false);
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('Missing');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'tender_id', 'status']);
        });

        Schema::create('construction_materials', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('material_code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('Item');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('stock_quantity', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('reorder_level', 15, 3)->default(0);
            $table->string('status')->default('Active');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'category', 'status']);
        });

        Schema::create('construction_material_requests', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $this->branch($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('construction_materials')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_number')->unique();
            $table->string('material_name');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('approved_quantity', 15, 3)->default(0);
            $table->decimal('issued_quantity', 15, 3)->default(0);
            $table->decimal('delivered_quantity', 15, 3)->default(0);
            $table->string('status')->default('Requested');
            $table->date('required_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_material_consumptions', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('boq_item_id')->nullable()->constrained('construction_boq_items')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('construction_materials')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('usage_date');
            $table->string('material_name');
            $table->decimal('planned_quantity', 15, 3)->default(0);
            $table->decimal('issued_quantity', 15, 3)->default(0);
            $table->decimal('actual_quantity', 15, 3)->default(0);
            $table->decimal('waste_quantity', 15, 3)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'usage_date']);
        });

        Schema::create('construction_contractors', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('type')->default('Subcontractor');
            $table->string('trade')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_pin')->nullable();
            $table->string('insurance')->nullable();
            $table->string('certification')->nullable();
            $table->decimal('performance_rating', 5, 2)->default(0);
            $table->json('financial_details')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'type', 'trade']);
        });

        Schema::create('construction_subcontracts', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->constrained('construction_contractors')->cascadeOnDelete();
            $table->string('subcontract_number')->unique();
            $table->text('scope')->nullable();
            $table->json('boq_item_ids')->nullable();
            $table->decimal('contract_sum', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->decimal('retention_percentage', 8, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_progress_measurements', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('boq_item_id')->nullable()->constrained('construction_boq_items')->nullOnDelete();
            $table->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('measurement_number')->unique();
            $table->string('location')->nullable();
            $table->decimal('measured_quantity', 15, 3)->default(0);
            $table->date('measurement_date');
            $table->string('status')->default('Draft');
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_certificates', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('period')->nullable();
            $table->decimal('contract_value', 15, 2)->default(0);
            $table->decimal('work_executed', 15, 2)->default(0);
            $table->decimal('materials_on_site', 15, 2)->default(0);
            $table->decimal('approved_variations', 15, 2)->default(0);
            $table->decimal('gross_certified', 15, 2)->default(0);
            $table->decimal('retention', 15, 2)->default(0);
            $table->decimal('advance_recovery', 15, 2)->default(0);
            $table->decimal('previous_certificates', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('net_certificate', 15, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_variations', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variation_number')->unique();
            $table->string('instruction_reference')->nullable();
            $table->text('description');
            $table->string('reason')->nullable();
            $table->decimal('cost_impact', 15, 2)->default(0);
            $table->integer('time_impact_days')->default(0);
            $table->json('boq_changes')->nullable();
            $table->date('submitted_date')->nullable();
            $table->date('approved_date')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_site_reports', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_number')->unique();
            $table->date('report_date');
            $table->string('weather')->nullable();
            $table->unsignedInteger('workforce_count')->default(0);
            $table->text('work_completed')->nullable();
            $table->text('activities_in_progress')->nullable();
            $table->text('materials_received')->nullable();
            $table->text('materials_used')->nullable();
            $table->text('equipment_used')->nullable();
            $table->text('delays')->nullable();
            $table->text('safety_issues')->nullable();
            $table->text('quality_issues')->nullable();
            $table->text('instructions')->nullable();
            $table->text('next_day_plan')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('Submitted');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'report_date']);
        });

        Schema::create('construction_site_diaries', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('event_type');
            $table->text('description');
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'event_date']);
        });

        Schema::create('construction_rfis', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rfi_number')->unique();
            $table->text('question');
            $table->string('drawing_reference')->nullable();
            $table->string('boq_reference')->nullable();
            $table->date('required_date')->nullable();
            $table->text('response')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_site_instructions', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('issuer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('instruction_number')->unique();
            $table->text('instruction');
            $table->date('instruction_date');
            $table->string('priority')->default('Medium');
            $table->date('due_date')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_quality_inspections', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_number')->unique();
            $table->string('inspection_type');
            $table->string('location')->nullable();
            $table->string('activity')->nullable();
            $table->date('inspection_date');
            $table->string('result')->default('Pass');
            $table->json('photos')->nullable();
            $table->text('comments')->nullable();
            $table->string('status')->default('Closed');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'result']);
        });

        Schema::create('construction_safety_incidents', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('construction_contractors')->nullOnDelete();
            $table->string('incident_number')->unique();
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->string('location')->nullable();
            $table->string('incident_type')->nullable();
            $table->string('severity')->default('Low');
            $table->text('description');
            $table->text('immediate_action')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'severity', 'status']);
        });

        Schema::create('construction_defects', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('construction_contractors')->nullOnDelete();
            $table->string('defect_number')->unique();
            $table->string('location')->nullable();
            $table->string('area')->nullable();
            $table->text('description');
            $table->date('reported_date')->nullable();
            $table->date('target_date')->nullable();
            $table->string('severity')->default('Medium');
            $table->json('photos')->nullable();
            $table->string('status')->default('Open');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        Schema::create('construction_equipment', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('construction_sites')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('equipment_code')->unique();
            $table->string('name');
            $table->string('equipment_type')->nullable();
            $table->decimal('hours_used', 15, 2)->default(0);
            $table->decimal('fuel_used', 15, 2)->default(0);
            $table->decimal('cost_per_hour', 15, 2)->default(0);
            $table->string('location')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('status')->default('Available');
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'status']);
        });

        Schema::create('construction_handovers', function (Blueprint $table) {
            $table->id();
            $this->scope($table);
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('handover_number')->unique();
            $table->json('checklist')->nullable();
            $table->date('practical_completion_date')->nullable();
            $table->date('handover_date')->nullable();
            $table->date('dlp_start_date')->nullable();
            $table->date('dlp_end_date')->nullable();
            $table->string('client_acceptance')->nullable();
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'business_id', 'project_id', 'status']);
        });

        $this->registerModule();
    }

    public function down(): void
    {
        foreach ([
            'construction_handovers',
            'construction_equipment',
            'construction_defects',
            'construction_safety_incidents',
            'construction_quality_inspections',
            'construction_site_instructions',
            'construction_rfis',
            'construction_site_diaries',
            'construction_site_reports',
            'construction_variations',
            'construction_certificates',
            'construction_progress_measurements',
            'construction_subcontracts',
            'construction_contractors',
            'construction_material_consumptions',
            'construction_material_requests',
            'construction_materials',
            'construction_tender_checklist_items',
            'construction_tenders',
            'construction_estimates',
            'construction_rate_components',
            'construction_boq_items',
            'construction_boq_sections',
            'construction_boqs',
            'construction_sites',
            'construction_project_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('slug', 'construction')->delete();
        }
    }

    private function scope(Blueprint $table): void
    {
        $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
        $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
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

        $definition = require base_path('Modules/Construction/module.php');

        $moduleId = DB::table('modules')->updateOrInsert(
            ['slug' => 'construction'],
            [
                'name' => 'Construction',
                'namespace' => 'Modules\\Construction',
                'type' => 'industry',
                'industry' => 'construction',
                'icon' => 'bi-building',
                'route' => 'construction.dashboard',
                'permissions' => json_encode($definition['permissions'] ?? []),
                'menu' => json_encode(['label' => 'Construction', 'route' => 'construction.dashboard']),
                'widgets' => json_encode($definition['widgets'] ?? []),
                'is_core' => false,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (Schema::hasTable('industry_modules')) {
            $moduleId = DB::table('modules')->where('slug', 'construction')->value('id');
            if ($moduleId) {
                DB::table('industry_modules')->updateOrInsert(
                    ['industry' => 'construction', 'module_id' => $moduleId],
                    ['enabled_by_default' => true, 'updated_at' => now(), 'created_at' => now()]
                );

                if (Schema::hasTable('tenants') && Schema::hasTable('tenant_modules')) {
                    DB::table('tenants')
                        ->where('industry', 'construction')
                        ->pluck('id')
                        ->each(function ($tenantId) use ($moduleId) {
                            DB::table('tenant_modules')->updateOrInsert(
                                ['tenant_id' => $tenantId, 'module_id' => $moduleId],
                                ['enabled' => true, 'enabled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
                            );
                        });
                }
            }
        }
    }
};
