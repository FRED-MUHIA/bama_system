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
        $this->createTenantTables();
        $this->createModuleTables();
        $this->createSubscriptionTables();
        $this->createThemeTables();
        $this->createDashboardTables();
        $this->addTenantColumns();
        $this->seedPlatformDefaults();
    }

    public function down(): void
    {
        foreach ([
            'tenant_dashboard_widgets',
            'dashboard_widgets',
            'tenant_themes',
            'subscription_usage',
            'subscription_features',
            'subscriptions',
            'plans',
            'industry_modules',
            'tenant_modules',
            'modules',
            'tenant_user',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createTenantTables(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('industry')->nullable();
                $table->string('status')->default('trial');
                $table->string('primary_domain')->nullable()->unique();
                $table->json('settings')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('tenant_user')) {
            Schema::create('tenant_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role')->default('owner');
                $table->string('status')->default('active');
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    private function createModuleTables(): void
    {
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('namespace')->nullable();
                $table->string('type')->default('core');
                $table->string('industry')->nullable();
                $table->string('icon')->nullable();
                $table->string('route')->nullable();
                $table->json('permissions')->nullable();
                $table->json('menu')->nullable();
                $table->json('widgets')->nullable();
                $table->boolean('is_core')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_modules')) {
            Schema::create('tenant_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('module_id')->constrained()->cascadeOnDelete();
                $table->boolean('enabled')->default(true);
                $table->json('settings')->nullable();
                $table->timestamp('enabled_at')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'module_id']);
            });
        }

        if (! Schema::hasTable('industry_modules')) {
            Schema::create('industry_modules', function (Blueprint $table) {
                $table->id();
                $table->string('industry');
                $table->foreignId('module_id')->constrained()->cascadeOnDelete();
                $table->boolean('enabled_by_default')->default(true);
                $table->timestamps();
                $table->unique(['industry', 'module_id']);
            });
        }
    }

    private function createSubscriptionTables(): void
    {
        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->decimal('monthly_price', 12, 2)->default(0);
                $table->string('currency', 3)->default('KES');
                $table->json('limits')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained()->restrictOnDelete();
                $table->string('status')->default('trialing');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('renews_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (! Schema::hasTable('subscription_features')) {
            Schema::create('subscription_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
                $table->string('feature');
                $table->string('value')->nullable();
                $table->integer('limit')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();
                $table->unique(['plan_id', 'feature']);
            });
        }

        if (! Schema::hasTable('subscription_usage')) {
            Schema::create('subscription_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('feature');
                $table->unsignedBigInteger('used')->default(0);
                $table->timestamp('resets_at')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'feature']);
            });
        }
    }

    private function createThemeTables(): void
    {
        if (! Schema::hasTable('tenant_themes')) {
            Schema::create('tenant_themes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete()->unique();
                $table->string('primary_color')->default('#00A651');
                $table->string('secondary_color')->default('#000000');
                $table->string('accent_color')->default('#00A651');
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->boolean('dark_mode_enabled')->default(true);
                $table->json('custom_colors')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createDashboardTables(): void
    {
        if (! Schema::hasTable('dashboard_widgets')) {
            Schema::create('dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('module_slug')->nullable();
                $table->string('industry')->nullable();
                $table->string('component')->nullable();
                $table->string('permission')->nullable();
                $table->json('settings_schema')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_dashboard_widgets')) {
            Schema::create('tenant_dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('dashboard_widget_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unsignedSmallInteger('width')->default(4);
                $table->boolean('enabled')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'user_id', 'dashboard_widget_id'], 'tenant_dashboard_widget_unique');
            });
        }
    }

    private function addTenantColumns(): void
    {
        foreach ($this->tenantTables() as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index('tenant_id');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'current_tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('current_tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }
    }

    private function tenantTables(): array
    {
        return [
            'businesses',
            'company_settings',
            'clients',
            'contacts',
            'sites',
            'projects',
            'payment_methods',
            'terms_conditions',
            'quotations',
            'invoices',
            'payments',
            'receipts',
            'email_logs',
            'product_categories',
            'products',
            'pos_orders',
            'pos_order_payments',
            'letter_templates',
            'letters',
            'document_media',
            'template_categories',
            'business_templates',
            'project_costs',
            'project_expenses',
            'project_documents',
            'document_templates',
            'suppliers',
            'supplier_quotes',
            'purchase_orders',
            'goods_received_notes',
            'supplier_invoices',
            'supplier_payments',
            'warranties',
            'warranty_claims',
            'client_portal_invitations',
            'finance_accounts',
            'finance_periods',
            'journal_entries',
            'bank_accounts',
            'bank_transactions',
            'bank_statement_lines',
            'fixed_assets',
            'tax_records',
            'departments',
            'cost_centers',
            'accounting_budgets',
            'accounting_allocations',
            'budget_alerts',
            'accounting_audit_logs',
            'branches',
            'iam_roles',
            'teams',
            'user_invitations',
            'approval_workflows',
            'approval_requests',
            'admin_audit_logs',
            'security_settings',
            'mail_settings',
        ];
    }

    private function seedPlatformDefaults(): void
    {
        $this->seedPlans();
        $this->seedModules();
        $this->seedStarterTenant();
    }

    private function seedPlans(): void
    {
        $plans = [
            'starter' => ['Starter', 0, ['users' => 5, 'storage_mb' => 1024, 'branches' => 1, 'projects' => 10, 'api_access' => false]],
            'growth' => ['Growth', 4900, ['users' => 20, 'storage_mb' => 10240, 'branches' => 5, 'projects' => 100, 'api_access' => true]],
            'professional' => ['Professional', 14900, ['users' => 75, 'storage_mb' => 51200, 'branches' => 20, 'projects' => 500, 'api_access' => true]],
            'enterprise' => ['Enterprise', 0, ['users' => null, 'storage_mb' => null, 'branches' => null, 'projects' => null, 'api_access' => true]],
        ];

        foreach ($plans as $slug => [$name, $price, $limits]) {
            $planId = DB::table('plans')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'monthly_price' => $price, 'currency' => 'KES', 'limits' => json_encode($limits), 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );

            $id = DB::table('plans')->where('slug', $slug)->value('id');
            foreach ($limits as $feature => $limit) {
                DB::table('subscription_features')->updateOrInsert(
                    ['plan_id' => $id, 'feature' => $feature],
                    ['limit' => is_bool($limit) || is_null($limit) ? null : $limit, 'value' => is_bool($limit) ? ($limit ? 'true' : 'false') : null, 'enabled' => $limit !== false, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    private function seedModules(): void
    {
        $modules = [
            ['authentication', 'Authentication', 'core', true, 'bi-shield-lock', null],
            ['iam', 'IAM', 'core', true, 'bi-key', 'administration.index'],
            ['crm', 'CRM', 'core', true, 'bi-people', 'clients.index'],
            ['documents', 'Documents', 'core', true, 'bi-file-earmark-text', 'quotations.index'],
            ['projects', 'Projects', 'core', true, 'bi-kanban', 'projects.index'],
            ['finance', 'Finance', 'core', true, 'bi-bank', 'finance.index'],
            ['accounting', 'Accounting', 'core', true, 'bi-diagram-3', 'accounting.index'],
            ['expenses', 'Expenses', 'core', true, 'bi-receipt', 'accounting.index'],
            ['notifications', 'Notifications', 'core', true, 'bi-bell', null],
            ['reporting', 'Reporting', 'core', true, 'bi-bar-chart', 'erp.reports'],
            ['administration', 'Administration', 'core', true, 'bi-gear', 'administration.index'],
            ['portal', 'Portal', 'core', true, 'bi-window-sidebar', 'erp.portal'],
            ['construction', 'Construction', 'industry', false, 'bi-building', 'erp.procurement'],
            ['healthcare', 'Healthcare', 'industry', false, 'bi-heart-pulse', null],
            ['education', 'Education', 'industry', false, 'bi-mortarboard', null],
            ['retail', 'Retail', 'industry', false, 'bi-shop', 'pos-orders.index'],
            ['manufacturing', 'Manufacturing', 'industry', false, 'bi-gear-wide-connected', null],
            ['hospitality', 'Hospitality', 'industry', false, 'bi-cup-hot', null],
            ['logistics', 'Logistics', 'industry', false, 'bi-truck', null],
            ['real-estate', 'Real Estate', 'industry', false, 'bi-house-door', null],
            ['professional-services', 'Professional Services', 'industry', false, 'bi-briefcase', null],
        ];

        foreach ($modules as [$slug, $name, $type, $core, $icon, $route]) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'namespace' => 'Modules\\'.Str::studly(str_replace('-', '_', $slug)),
                    'type' => $type,
                    'industry' => $type === 'industry' ? $name : null,
                    'icon' => $icon,
                    'route' => $route,
                    'is_core' => $core,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $industryMap = [
            'Construction' => ['construction', 'projects', 'documents', 'finance', 'accounting'],
            'Healthcare' => ['healthcare', 'crm', 'finance', 'reporting'],
            'Education' => ['education', 'crm', 'finance', 'reporting'],
            'Retail' => ['retail', 'crm', 'finance', 'accounting'],
            'Manufacturing' => ['manufacturing', 'inventory', 'finance', 'accounting'],
            'Hospitality' => ['hospitality', 'crm', 'finance'],
            'Logistics' => ['logistics', 'crm', 'finance'],
            'RealEstate' => ['real-estate', 'crm', 'finance'],
            'ProfessionalServices' => ['professional-services', 'crm', 'projects', 'finance'],
        ];

        foreach ($industryMap as $industry => $slugs) {
            foreach ($slugs as $slug) {
                $moduleId = DB::table('modules')->where('slug', $slug)->value('id');
                if ($moduleId) {
                    DB::table('industry_modules')->updateOrInsert(
                        ['industry' => $industry, 'module_id' => $moduleId],
                        ['enabled_by_default' => true, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        }

        foreach ([
            ['crm-summary', 'CRM Summary', 'crm'],
            ['finance-kpis', 'Finance KPIs', 'finance'],
            ['project-health', 'Project Health', 'projects'],
            ['inventory-summary', 'Inventory Summary', 'retail'],
            ['construction-sites', 'Site Management', 'construction'],
        ] as [$slug, $name, $module]) {
            DB::table('dashboard_widgets')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'module_slug' => $module, 'component' => 'widgets.'.$slug, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    private function seedStarterTenant(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        $tenantId = DB::table('tenants')->where('slug', 'bama')->value('id');
        if (! $tenantId) {
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => 'BAMA',
                'slug' => 'bama',
                'industry' => 'ProfessionalServices',
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('businesses')->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        foreach ($this->tenantTables() as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
            }
        }

        $planId = DB::table('plans')->where('slug', 'professional')->value('id');
        if ($planId) {
            DB::table('subscriptions')->updateOrInsert(
                ['tenant_id' => $tenantId],
                ['plan_id' => $planId, 'status' => 'active', 'starts_at' => now(), 'renews_at' => now()->addMonth(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        DB::table('tenant_themes')->updateOrInsert(
            ['tenant_id' => $tenantId],
            ['primary_color' => '#00A651', 'secondary_color' => '#000000', 'accent_color' => '#00A651', 'dark_mode_enabled' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        foreach (DB::table('modules')->where('is_core', true)->orWhereIn('slug', ['retail', 'professional-services'])->get() as $module) {
            DB::table('tenant_modules')->updateOrInsert(
                ['tenant_id' => $tenantId, 'module_id' => $module->id],
                ['enabled' => true, 'enabled_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        if (Schema::hasTable('users')) {
            foreach (DB::table('users')->where('role', '!=', 'client_portal')->get() as $user) {
                DB::table('tenant_user')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'user_id' => $user->id],
                    ['role' => $user->role === 'admin' ? 'owner' : 'member', 'status' => 'active', 'joined_at' => now(), 'updated_at' => now(), 'created_at' => now()]
                );
                DB::table('users')->where('id', $user->id)->whereNull('current_tenant_id')->update(['current_tenant_id' => $tenantId]);
            }
        }
    }
};
