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
        $this->createMembershipTables();
        $this->extendSharedPayments();
        $this->registerFitnessPlatform();
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'payable_type')) {
                    $table->dropIndex('payments_payable_index');
                    $table->dropColumn(['payable_type', 'payable_id']);
                }
            });
        }

        Schema::dropIfExists('fitness_membership_events');
        Schema::dropIfExists('fitness_membership_freezes');
        Schema::dropIfExists('fitness_member_memberships');
        Schema::dropIfExists('fitness_members');
        Schema::dropIfExists('fitness_membership_plans');
    }

    private function createMembershipTables(): void
    {
        if (! Schema::hasTable('fitness_membership_plans')) {
            Schema::create('fitness_membership_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('plan_type')->default('Monthly');
                $table->text('description')->nullable();
                $table->string('currency', 3)->default('KES');
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('joining_fee', 14, 2)->default(0);
                $table->decimal('renewal_fee', 14, 2)->default(0);
                $table->unsignedInteger('duration_days');
                $table->unsignedInteger('session_credits')->nullable();
                $table->boolean('freeze_allowed')->default(false);
                $table->unsignedInteger('guest_passes')->default(0);
                $table->string('status')->default('Active');
                $table->timestamps();

                $table->unique(['business_id', 'name']);
                $table->index(['business_id', 'status']);
                $table->index(['tenant_id', 'business_id']);
            });
        }

        if (! Schema::hasTable('fitness_members')) {
            Schema::create('fitness_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_trainer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('member_number');
                $table->string('photo_path')->nullable();
                $table->string('gender')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('address')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('occupation')->nullable();
                $table->date('join_date')->nullable();
                $table->string('status')->default('Pending');
                $table->string('qr_code')->unique();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'member_number']);
                $table->unique(['business_id', 'client_id']);
                $table->index(['business_id', 'status']);
                $table->index(['business_id', 'assigned_trainer_id']);
                $table->index(['tenant_id', 'business_id']);
            });
        }

        if (! Schema::hasTable('fitness_member_memberships')) {
            Schema::create('fitness_member_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_id')->constrained('fitness_members')->cascadeOnDelete();
                $table->foreignId('fitness_membership_plan_id')->constrained('fitness_membership_plans')->restrictOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->string('membership_number');
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->date('renewal_date')->nullable();
                $table->boolean('auto_renew')->default(false);
                $table->string('status')->default('Pending');
                $table->unsignedInteger('session_credits_remaining')->nullable();
                $table->unsignedInteger('guest_passes_remaining')->default(0);
                $table->decimal('price_charged', 14, 2)->default(0);
                $table->decimal('joining_fee_charged', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->timestamp('last_renewed_at')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'membership_number']);
                $table->index(['business_id', 'status']);
                $table->index(['business_id', 'starts_at', 'ends_at']);
                $table->index(['business_id', 'renewal_date']);
                $table->index(['tenant_id', 'business_id']);
            });
        }

        if (! Schema::hasTable('fitness_membership_freezes')) {
            Schema::create('fitness_membership_freezes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_membership_id')->constrained('fitness_member_memberships')->cascadeOnDelete();
                $table->date('starts_at');
                $table->date('ends_at');
                $table->string('reason');
                $table->string('status')->default('Active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->index(['business_id', 'starts_at', 'ends_at']);
            });
        }

        if (! Schema::hasTable('fitness_membership_events')) {
            Schema::create('fitness_membership_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fitness_member_membership_id')->constrained('fitness_member_memberships')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event');
                $table->text('notes')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'event']);
                $table->index(['business_id', 'created_at']);
            });
        }
    }

    private function extendSharedPayments(): void
    {
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'payable_type')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payable_type')->nullable()->after('invoice_id');
            $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');
            $table->index(['payable_type', 'payable_id'], 'payments_payable_index');
        });
    }

    private function registerFitnessPlatform(): void
    {
        $now = now();
        $permissions = [
            'fitness.view',
            'fitness.manage',
            'fitness.reports',
            'fitness.memberships.view',
            'fitness.memberships.create',
            'fitness.memberships.edit',
            'fitness.memberships.delete',
            'fitness.members.view',
            'fitness.members.create',
            'fitness.members.edit',
            'fitness.members.delete',
            'fitness.payments.view',
            'fitness.payments.manage',
        ];

        if (Schema::hasTable('iam_permissions')) {
            foreach ($permissions as $permission) {
                DB::table('iam_permissions')->updateOrInsert(
                    ['name' => $permission],
                    ['module' => 'fitness', 'description' => Str::headline(str_replace('.', ' ', $permission)), 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->updateOrInsert(
                ['slug' => 'fitness'],
                [
                    'name' => 'Fitness & Gym',
                    'namespace' => 'Modules\\Fitness',
                    'type' => 'industry',
                    'industry' => 'fitness',
                    'icon' => 'bi-activity',
                    'route' => 'fitness.dashboard',
                    'permissions' => json_encode($permissions),
                    'menu' => json_encode(['label' => 'Fitness & Gym', 'group' => 'Industry', 'icon' => 'bi-activity', 'route' => 'fitness.dashboard']),
                    'widgets' => json_encode(['fitness-active-members', 'fitness-expiring-memberships', 'fitness-revenue-month']),
                    'is_core' => false,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $moduleId = DB::table('modules')->where('slug', 'fitness')->value('id');
            if ($moduleId && Schema::hasTable('industry_modules')) {
                DB::table('industry_modules')->updateOrInsert(
                    ['industry' => 'fitness', 'module_id' => $moduleId],
                    ['enabled_by_default' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('dashboard_widgets')) {
            foreach ([
                ['fitness-active-members', 'Active Members', 'fitness.members.view'],
                ['fitness-expiring-memberships', 'Expiring Memberships', 'fitness.memberships.view'],
                ['fitness-revenue-month', 'Fitness Revenue MTD', 'fitness.payments.view'],
            ] as [$slug, $name, $permission]) {
                DB::table('dashboard_widgets')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'module_slug' => 'fitness',
                        'industry' => 'fitness',
                        'component' => 'fitness.widgets.metric-card',
                        'permission' => $permission,
                        'settings_schema' => json_encode(['supports_period_filters' => true]),
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('plans') && Schema::hasTable('subscription_features')) {
            $features = [
                'starter' => ['fitness.memberships' => 100, 'fitness.members' => 100],
                'growth' => ['fitness.memberships' => 1000, 'fitness.members' => 1000],
                'professional' => ['fitness.memberships' => null, 'fitness.members' => null, 'fitness.payments' => null],
                'enterprise' => ['fitness.memberships' => null, 'fitness.members' => null, 'fitness.payments' => null],
            ];

            foreach ($features as $planSlug => $planFeatures) {
                $planId = DB::table('plans')->where('slug', $planSlug)->value('id');
                if (! $planId) {
                    continue;
                }

                foreach ($planFeatures as $feature => $limit) {
                    DB::table('subscription_features')->updateOrInsert(
                        ['plan_id' => $planId, 'feature' => $feature],
                        ['limit' => $limit, 'value' => null, 'enabled' => true, 'updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }
    }
};
