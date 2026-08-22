<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'printing_alerts',
            'printing_delivery_notes',
            'printing_dispatches',
            'printing_outsourcing_orders',
            'printing_wastes',
            'printing_reprints',
            'printing_quality_checks',
            'printing_schedules',
            'printing_operations',
            'printing_machine_maintenance',
            'printing_job_costs',
            'printing_material_reservations',
            'printing_proof_approvals',
            'printing_artworks',
            'printing_job_tickets',
            'printing_jobs',
            'printing_estimates',
            'printing_pricing_rules',
            'printing_materials',
            'printing_machines',
            'printing_finishing_options',
            'printing_print_methods',
            'printing_product_templates',
            'printing_client_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createOperationalTables(): void
    {
        $this->createIfMissing('printing_client_profiles', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('client_type')->default('SME')->index();
            $table->string('lead_source')->nullable()->index();
            $table->json('preferred_products')->nullable();
            $table->string('print_frequency')->nullable()->index();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->decimal('overdue_balance', 14, 2)->default(0);
            $table->boolean('credit_hold')->default(false)->index();
            $table->string('price_tier')->default('Standard')->index();
            $table->text('client_notes')->nullable();
            $table->json('previous_jobs')->nullable();
            $table->json('artwork_history')->nullable();
            $table->string('pipeline_stage')->default('Lead')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'client_id'], 'printing_client_profiles_tenant_client_unique');
        });

        $this->createIfMissing('printing_product_templates', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('template_code');
            $table->string('name');
            $table->string('category')->index();
            $table->json('specifications')->nullable();
            $table->json('default_costing')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'template_code'], 'printing_templates_tenant_code_unique');
        });

        $this->createIfMissing('printing_print_methods', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('method_code');
            $table->string('name');
            $table->unsignedBigInteger('machine_id')->nullable()->index();
            $table->decimal('setup_cost', 14, 2)->default(0);
            $table->unsignedInteger('minimum_quantity')->default(1);
            $table->unsignedInteger('estimated_production_minutes')->default(0);
            $table->json('costing_rules')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'method_code'], 'printing_methods_tenant_code_unique');
        });

        $this->createIfMissing('printing_finishing_options', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('option_code');
            $table->string('name');
            $table->decimal('cost', 14, 2)->default(0);
            $table->unsignedInteger('production_minutes')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'option_code'], 'printing_finishing_tenant_code_unique');
        });

        $this->createIfMissing('printing_machines', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('machine_code');
            $table->string('name');
            $table->string('machine_type')->index();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->string('capacity')->nullable();
            $table->decimal('cost_per_hour', 14, 2)->default(0);
            $table->string('status')->default('Available')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'machine_code'], 'printing_machines_tenant_code_unique');
        });

        $this->createIfMissing('printing_materials', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('material_code');
            $table->string('name');
            $table->string('category')->index();
            $table->string('unit')->default('pcs');
            $table->string('gsm')->nullable();
            $table->decimal('width', 14, 3)->nullable();
            $table->decimal('length', 14, 3)->nullable();
            $table->string('color')->nullable()->index();
            $table->string('batch_number')->nullable()->index();
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('stock_quantity', 14, 3)->default(0);
            $table->decimal('reserved_quantity', 14, 3)->default(0);
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->string('stock_type')->default('Raw Materials')->index();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'material_code'], 'printing_materials_tenant_code_unique');
        });

        $this->createIfMissing('printing_pricing_rules', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->string('rule_code');
            $table->string('name');
            $table->string('rule_type')->index();
            $table->string('client_tier')->nullable()->index();
            $table->string('product_category')->nullable()->index();
            $table->decimal('rate', 14, 4)->default(0);
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'rule_code'], 'printing_pricing_tenant_code_unique');
        });

        $this->createIfMissing('printing_estimates', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_template_id')->nullable()->constrained('printing_product_templates')->nullOnDelete();
            $table->foreignId('print_method_id')->nullable()->constrained('printing_print_methods')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estimate_number');
            $table->string('product_name');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->json('specifications')->nullable();
            $table->json('finishing')->nullable();
            $table->decimal('artwork_charges', 14, 2)->default(0);
            $table->decimal('setup_charges', 14, 2)->default(0);
            $table->decimal('machine_cost', 14, 2)->default(0);
            $table->decimal('labor_cost', 14, 2)->default(0);
            $table->decimal('material_cost', 14, 2)->default(0);
            $table->decimal('outsourcing_cost', 14, 2)->default(0);
            $table->decimal('delivery_cost', 14, 2)->default(0);
            $table->decimal('markup', 8, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('selling_price', 14, 2)->default(0);
            $table->decimal('estimated_profit', 14, 2)->default(0);
            $table->string('status')->default('Draft')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'estimate_number'], 'printing_estimates_tenant_number_unique');
        });

        $this->createIfMissing('printing_jobs', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('estimate_id')->nullable()->constrained('printing_estimates')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('printing_machines')->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('job_number');
            $table->string('product_name');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->json('specifications')->nullable();
            $table->string('artwork_path')->nullable();
            $table->date('delivery_date')->nullable()->index();
            $table->string('priority')->default('Normal')->index();
            $table->json('materials_required')->nullable();
            $table->text('production_notes')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'job_number'], 'printing_jobs_tenant_number_unique');
            $table->index(['tenant_id', 'status', 'delivery_date'], 'printing_jobs_tenant_status_due_idx');
        });

        $this->createIfMissing('printing_job_tickets', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->string('ticket_number');
            $table->string('qr_token')->unique();
            $table->string('barcode')->nullable()->index();
            $table->json('ticket_data')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'ticket_number'], 'printing_tickets_tenant_number_unique');
        });

        $this->createIfMissing('printing_artworks', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('artwork_number');
            $table->unsignedInteger('version')->default(1);
            $table->string('file_path')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('approval_status')->default('Not Received')->index();
            $table->text('revision_notes')->nullable();
            $table->string('status')->default('Not Received')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'artwork_number', 'version'], 'printing_artwork_tenant_number_version_unique');
        });

        $this->createIfMissing('printing_proof_approvals', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('artwork_id')->constrained('printing_artworks')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Sent to Client')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedInteger('approved_artwork_version')->nullable();
            $table->json('audit_trail')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('printing_material_reservations', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('printing_materials')->cascadeOnDelete();
            $table->decimal('required_quantity', 14, 3)->default(0);
            $table->decimal('reserved_quantity', 14, 3)->default(0);
            $table->decimal('consumed_quantity', 14, 3)->default(0);
            $table->string('status')->default('Reserved')->index();
            $table->timestamps();
        });

        $this->createIfMissing('printing_job_costs', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->decimal('estimated_material_cost', 14, 2)->default(0);
            $table->decimal('actual_material_cost', 14, 2)->default(0);
            $table->decimal('machine_cost', 14, 2)->default(0);
            $table->decimal('labor_cost', 14, 2)->default(0);
            $table->decimal('artwork_cost', 14, 2)->default(0);
            $table->decimal('finishing_cost', 14, 2)->default(0);
            $table->decimal('outsourcing_cost', 14, 2)->default(0);
            $table->decimal('transport_cost', 14, 2)->default(0);
            $table->decimal('overhead_allocation', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('selling_price', 14, 2)->default(0);
            $table->decimal('gross_profit', 14, 2)->default(0);
            $table->decimal('margin_percent', 8, 2)->default(0);
            $table->decimal('variance', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'job_id'], 'printing_job_costs_tenant_job_unique');
        });

        $this->createIfMissing('printing_machine_maintenance', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('machine_id')->constrained('printing_machines')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('maintenance_number');
            $table->string('maintenance_type')->index();
            $table->date('service_date')->index();
            $table->decimal('cost', 14, 2)->default(0);
            $table->json('parts_used')->nullable();
            $table->date('next_service_date')->nullable()->index();
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'maintenance_number'], 'printing_maint_tenant_number_unique');
        });

        $this->createIfMissing('printing_operations', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('printing_machines')->nullOnDelete();
            $table->string('stage')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('quantity_produced', 14, 3)->default(0);
            $table->decimal('quantity_rejected', 14, 3)->default(0);
            $table->json('material_used')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Pending')->index();
            $table->timestamps();
        });

        $this->createIfMissing('printing_schedules', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('printing_machines')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->string('view_type')->default('Machine Schedule')->index();
            $table->string('status')->default('Scheduled')->index();
            $table->timestamps();
            $table->index(['tenant_id', 'machine_id', 'starts_at', 'ends_at'], 'printing_schedule_machine_window_idx');
        });

        $this->createIfMissing('printing_quality_checks', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspection_date')->nullable()->index();
            $table->json('checkpoints')->nullable();
            $table->string('result')->default('Pass')->index();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->decimal('rejected_quantity', 14, 3)->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('printing_reprints', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('original_job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->string('reprint_number');
            $table->string('reason')->index();
            $table->decimal('rejected_quantity', 14, 3)->default(0);
            $table->decimal('reprint_quantity', 14, 3)->default(0);
            $table->string('responsible_department')->nullable()->index();
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('approval_status')->default('Pending')->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'reprint_number'], 'printing_reprints_tenant_number_unique');
        });

        $this->createIfMissing('printing_wastes', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->nullable()->constrained('printing_jobs')->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('printing_materials')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('printing_machines')->nullOnDelete();
            $table->string('waste_type')->index();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('reason')->nullable()->index();
            $table->timestamps();
        });

        $this->createIfMissing('printing_outsourcing_orders', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->string('service')->index();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('cost', 14, 2)->default(0);
            $table->date('expected_completion')->nullable()->index();
            $table->string('delivery_status')->default('Waiting')->index();
            $table->string('quality_status')->default('Pending')->index();
            $table->timestamps();
        });

        $this->createIfMissing('printing_dispatches', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dispatch_number');
            $table->string('status')->default('Waiting')->index();
            $table->text('delivery_address')->nullable();
            $table->string('vehicle')->nullable();
            $table->string('courier')->nullable();
            $table->date('dispatch_date')->nullable()->index();
            $table->date('delivery_date')->nullable()->index();
            $table->string('proof_of_delivery_path')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'dispatch_number'], 'printing_dispatch_tenant_number_unique');
        });

        $this->createIfMissing('printing_delivery_notes', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->foreignId('dispatch_id')->nullable()->constrained('printing_dispatches')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('printing_jobs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('delivery_note_number');
            $table->json('products')->nullable();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->text('delivery_address')->nullable();
            $table->string('driver')->nullable();
            $table->string('receiver')->nullable();
            $table->date('delivery_date')->nullable()->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'delivery_note_number'], 'printing_delivery_notes_tenant_number_unique');
        });

        $this->createIfMissing('printing_alerts', function (Blueprint $table) {
            $this->tenantBusiness($table);
            $table->nullableMorphs('alertable', 'printing_alertable_idx');
            $table->string('type')->index();
            $table->string('severity')->default('info')->index();
            $table->string('message');
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
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

    private function registerModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $definition = require base_path('Modules/PrintingBranding/module.php');
        DB::table('modules')->updateOrInsert(
            ['slug' => 'printing-branding'],
            [
                'name' => 'Printing & Branding',
                'namespace' => 'Modules\\PrintingBranding',
                'type' => 'industry',
                'industry' => 'printing_branding',
                'icon' => 'bi-printer',
                'route' => 'printing-branding.dashboard',
                'permissions' => json_encode($definition['permissions'] ?? ['printing.view']),
                'menu' => json_encode(['label' => 'Printing & Branding', 'route' => 'printing-branding.dashboard']),
                'widgets' => json_encode($definition['widgets'] ?? ['printing-overview']),
                'is_core' => false,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (Schema::hasTable('industry_modules')) {
            $moduleId = DB::table('modules')->where('slug', 'printing-branding')->value('id');
            if ($moduleId) {
                foreach (['printing_branding', 'printing-branding'] as $industry) {
                    DB::table('industry_modules')->updateOrInsert(
                        ['industry' => $industry, 'module_id' => $moduleId],
                        ['enabled_by_default' => true, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['printing-overview', 'Printing Operations Overview'],
                ['printing-sales-trend', 'Printing Sales Trend'],
                ['printing-jobs-by-status', 'Printing Jobs by Status'],
                ['printing-machine-utilization', 'Printing Machine Utilization'],
                ['printing-waste-trends', 'Printing Waste Trends'],
            ] as [$slug, $name]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'printing-branding',
                        'industry' => 'printing_branding',
                        'component' => 'widgets.'.$slug,
                        'permission' => 'printing.dashboard',
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
};
