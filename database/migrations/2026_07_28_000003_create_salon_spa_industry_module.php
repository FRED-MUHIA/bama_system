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
            'salon_wellness_enrollments',
            'salon_wellness_programs',
            'salon_commissions',
            'salon_product_consumptions',
            'salon_loyalty_accounts',
            'salon_gift_cards',
            'salon_packages',
            'salon_memberships',
            'salon_membership_plans',
            'salon_treatments',
            'salon_consultations',
            'salon_staff_schedules',
            'salon_appointment_services',
            'salon_appointments',
            'salon_resources',
            'salon_services',
            'salon_staff_profiles',
            'salon_client_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createOperationalTables(): void
    {
        if (! Schema::hasTable('salon_client_profiles')) {
            Schema::create('salon_client_profiles', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->string('client_code')->nullable()->index();
                $table->date('date_of_birth')->nullable();
                $table->string('gender')->nullable();
                $table->json('preferences')->nullable();
                $table->json('allergies')->nullable();
                $table->json('skin_hair_profile')->nullable();
                $table->string('loyalty_tier')->default('Standard');
                $table->unsignedInteger('lifetime_visits')->default(0);
                $table->decimal('lifetime_spend', 14, 2)->default(0);
                $table->timestamp('last_visit_at')->nullable();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
                $table->unique(['tenant_id', 'client_id'], 'salon_client_tenant_client_unique');
            });
        }

        if (! Schema::hasTable('salon_staff_profiles')) {
            Schema::create('salon_staff_profiles', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('staff_code')->nullable()->index();
                $table->string('display_name');
                $table->string('role_title')->nullable();
                $table->json('specialties')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->decimal('hourly_rate', 12, 2)->default(0);
                $table->unsignedInteger('weekly_capacity_minutes')->default(2400);
                $table->string('status')->default('Active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_services')) {
            Schema::create('salon_services', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->string('name');
                $table->string('category')->nullable()->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('duration_minutes')->default(30);
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->boolean('requires_consultation')->default(false);
                $table->boolean('is_package_component')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_resources')) {
            Schema::create('salon_resources', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('name');
                $table->string('type')->default('Chair')->index();
                $table->unsignedInteger('capacity')->default(1);
                $table->string('status')->default('Available')->index();
                $table->json('equipment')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_appointments')) {
            Schema::create('salon_appointments', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('salon_client_profile_id')->nullable()->index();
                $table->unsignedBigInteger('salon_staff_profile_id')->nullable()->index();
                $table->unsignedBigInteger('salon_resource_id')->nullable()->index();
                $table->unsignedBigInteger('pos_order_id')->nullable()->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->string('appointment_number')->index();
                $table->string('channel')->default('Walk-in')->index();
                $table->timestamp('starts_at')->index();
                $table->timestamp('ends_at')->nullable();
                $table->string('status')->default('Booked')->index();
                $table->string('payment_status')->default('Unpaid')->index();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount_total', 14, 2)->default(0);
                $table->decimal('tax_total', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'appointment_number'], 'salon_appt_tenant_number_unique');
                $table->index(['tenant_id', 'business_id', 'starts_at'], 'salon_appt_tenant_business_start_idx');
            });
        }

        if (! Schema::hasTable('salon_appointment_services')) {
            Schema::create('salon_appointment_services', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_appointment_id')->index();
                $table->unsignedBigInteger('salon_service_id')->nullable()->index();
                $table->unsignedBigInteger('salon_staff_profile_id')->nullable()->index();
                $table->string('service_name');
                $table->unsignedInteger('duration_minutes')->default(30);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->decimal('tax', 14, 2)->default(0);
                $table->decimal('line_total', 14, 2)->default(0);
                $table->string('status')->default('Pending')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_staff_schedules')) {
            Schema::create('salon_staff_schedules', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('salon_staff_profile_id')->index();
                $table->date('work_date')->index();
                $table->time('starts_at');
                $table->time('ends_at');
                $table->string('shift_type')->default('Regular');
                $table->unsignedInteger('capacity_minutes')->default(480);
                $table->string('status')->default('Scheduled')->index();
                $table->timestamps();
                $table->unique(['salon_staff_profile_id', 'work_date', 'starts_at'], 'salon_schedule_staff_date_start_unique');
            });
        }

        if (! Schema::hasTable('salon_consultations')) {
            Schema::create('salon_consultations', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_client_profile_id')->index();
                $table->unsignedBigInteger('salon_appointment_id')->nullable()->index();
                $table->unsignedBigInteger('salon_staff_profile_id')->nullable()->index();
                $table->string('consultation_type')->default('Beauty');
                $table->json('observations')->nullable();
                $table->json('recommendations')->nullable();
                $table->json('contraindications')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_treatments')) {
            Schema::create('salon_treatments', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_client_profile_id')->index();
                $table->unsignedBigInteger('salon_appointment_id')->nullable()->index();
                $table->unsignedBigInteger('salon_service_id')->nullable()->index();
                $table->unsignedBigInteger('salon_staff_profile_id')->nullable()->index();
                $table->string('name');
                $table->date('performed_on')->index();
                $table->text('notes')->nullable();
                $table->json('products_used')->nullable();
                $table->json('aftercare')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_membership_plans')) {
            Schema::create('salon_membership_plans', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->string('name');
                $table->string('billing_cycle')->default('Monthly');
                $table->decimal('price', 14, 2)->default(0);
                $table->unsignedInteger('visit_allowance')->nullable();
                $table->decimal('discount_rate', 5, 2)->default(0);
                $table->json('benefits')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_memberships')) {
            Schema::create('salon_memberships', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_client_profile_id')->index();
                $table->unsignedBigInteger('salon_membership_plan_id')->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->string('membership_number')->index();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->unsignedInteger('visits_remaining')->nullable();
                $table->decimal('balance', 14, 2)->default(0);
                $table->string('status')->default('Active')->index();
                $table->timestamps();
                $table->unique(['tenant_id', 'membership_number'], 'salon_membership_tenant_number_unique');
            });
        }

        if (! Schema::hasTable('salon_packages')) {
            Schema::create('salon_packages', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->string('name');
                $table->decimal('price', 14, 2)->default(0);
                $table->unsignedInteger('valid_days')->default(90);
                $table->json('service_ids')->nullable();
                $table->json('benefits')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_gift_cards')) {
            Schema::create('salon_gift_cards', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->string('card_number')->index();
                $table->decimal('initial_value', 14, 2);
                $table->decimal('balance', 14, 2);
                $table->string('currency', 3)->default('KES');
                $table->date('expires_on')->nullable();
                $table->string('status')->default('Active')->index();
                $table->json('transactions')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'card_number'], 'salon_gift_card_tenant_number_unique');
            });
        }

        if (! Schema::hasTable('salon_loyalty_accounts')) {
            Schema::create('salon_loyalty_accounts', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_client_profile_id')->index();
                $table->string('tier')->default('Standard');
                $table->unsignedInteger('points_balance')->default(0);
                $table->unsignedInteger('lifetime_points')->default(0);
                $table->timestamp('last_activity_at')->nullable();
                $table->json('ledger')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'salon_client_profile_id'], 'salon_loyalty_tenant_profile_unique');
            });
        }

        if (! Schema::hasTable('salon_product_consumptions')) {
            Schema::create('salon_product_consumptions', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_appointment_id')->nullable()->index();
                $table->unsignedBigInteger('salon_service_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('quantity', 12, 3);
                $table->string('unit')->default('pcs');
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('total_cost', 14, 2)->default(0);
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_commissions')) {
            Schema::create('salon_commissions', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_staff_profile_id')->index();
                $table->unsignedBigInteger('salon_appointment_id')->nullable()->index();
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->date('commission_date')->index();
                $table->decimal('base_amount', 14, 2)->default(0);
                $table->decimal('rate', 5, 2)->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('status')->default('Pending')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_wellness_programs')) {
            Schema::create('salon_wellness_programs', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->string('name');
                $table->string('category')->default('Wellness');
                $table->text('description')->nullable();
                $table->unsignedInteger('duration_days')->default(30);
                $table->decimal('price', 14, 2)->default(0);
                $table->json('milestones')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salon_wellness_enrollments')) {
            Schema::create('salon_wellness_enrollments', function (Blueprint $table) {
                $this->tenantBusiness($table);
                $table->unsignedBigInteger('salon_wellness_program_id')->index();
                $table->unsignedBigInteger('salon_client_profile_id')->index();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->string('status')->default('Active')->index();
                $table->json('progress')->nullable();
                $table->timestamps();
            });
        }
    }

    private function registerModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $now = now();
        $permissions = [
            'salon.view', 'salon.manage', 'salon.reports',
            'salon.appointments.view', 'salon.appointments.manage',
            'salon.staff.view', 'salon.staff.manage',
            'salon.services.view', 'salon.services.manage',
            'salon.pos.view', 'salon.pos.manage',
            'salon.memberships.view', 'salon.memberships.manage',
            'salon.loyalty.view', 'salon.loyalty.manage',
            'salon.consultations.view', 'salon.consultations.manage',
            'salon.treatments.view', 'salon.treatments.manage',
            'salon.inventory.view', 'salon.inventory.manage',
            'salon.commissions.view', 'salon.commissions.manage',
            'salon.wellness.view', 'salon.wellness.manage',
        ];

        DB::table('modules')->updateOrInsert(
            ['slug' => 'salon'],
            [
                'name' => 'Salon & Spa',
                'namespace' => 'Modules\\Salon',
                'type' => 'industry',
                'industry' => 'salon',
                'icon' => 'bi-scissors',
                'route' => 'salon.dashboard',
                'permissions' => json_encode($permissions),
                'menu' => json_encode(['label' => 'Salon & Spa', 'group' => 'Industry', 'icon' => 'bi-scissors', 'route' => 'salon.dashboard']),
                'widgets' => json_encode(['salon-overview', 'salon-appointments', 'salon-revenue', 'salon-staff-utilization']),
                'is_core' => false,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $moduleId = (int) DB::table('modules')->where('slug', 'salon')->value('id');

        if (Schema::hasTable('industry_modules') && $moduleId) {
            DB::table('industry_modules')->updateOrInsert(
                ['industry' => 'salon', 'module_id' => $moduleId],
                ['enabled_by_default' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'salon', 'description' => Str::headline(str_replace(['salon.', '.', '-'], ['', ' ', ' '], $permission)), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['salon-overview', 'Salon & Spa Overview', 'salon.view'],
                ['salon-appointments', 'Appointments Today', 'salon.appointments.view'],
                ['salon-revenue', 'Salon Revenue', 'salon.pos.view'],
                ['salon-staff-utilization', 'Staff Utilization', 'salon.staff.view'],
                ['salon-inventory-usage', 'Product Usage', 'salon.inventory.view'],
                ['salon-client-retention', 'Client Retention', 'salon.loyalty.view'],
            ] as [$slug, $name, $permission]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'salon',
                        'industry' => 'salon',
                        'component' => 'dashboard.widgets.metric-card',
                        'permission' => $permission,
                        'settings_schema' => json_encode(['supports_branch_filters' => true, 'supports_period_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    private function tenantBusiness(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
        $table->index(['tenant_id', 'business_id']);
    }
};
