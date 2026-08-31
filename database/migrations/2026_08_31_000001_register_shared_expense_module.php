<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $now = now();

        DB::table('modules')->updateOrInsert(
            ['slug' => 'expenses'],
            [
                'name' => 'Expenses',
                'namespace' => 'Modules\\Core',
                'type' => 'core',
                'industry' => null,
                'icon' => 'bi-receipt',
                'route' => 'accounting.index',
                'permissions' => json_encode(['expenses.view', 'expenses.manage', 'accounting.expenses.view', 'accounting.expenses.manage']),
                'menu' => json_encode([
                    'label' => 'Expenses',
                    'group' => 'Core',
                    'icon' => 'bi-receipt',
                    'route' => 'accounting.index',
                    'params' => ['tab' => 'allocations', 'direction' => 'Expense'],
                ]),
                'widgets' => json_encode(['expenses-summary']),
                'is_core' => true,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        if (Schema::hasTable('dashboard_widgets')) {
            DB::table('dashboard_widgets')->updateOrInsert(
                ['slug' => 'expenses-summary'],
                [
                    'name' => 'Expenses Summary',
                    'module_slug' => 'expenses',
                    'industry' => null,
                    'component' => 'dashboard.widgets.metric-card',
                    'permission' => 'expenses.view',
                    'settings_schema' => json_encode([
                        'supports_tenant_filters' => true,
                        'supports_period_filters' => true,
                    ]),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Shared registry data is retained to avoid disabling existing tenant menus.
    }
};
