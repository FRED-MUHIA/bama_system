<?php

namespace App\Services;

use App\Support\ActiveTenant;
use App\Support\SchemaCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationManager
{
    public function sidebar(): Collection
    {
        $industryItems = $this->industrySidebar();

        if ($industryItems->isNotEmpty()) {
            return $industryItems;
        }

        $items = collect([
            ['module' => 'core', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-grid'],
            ['module' => 'shared-communication', 'shared' => true, 'label' => 'Messaging', 'route' => 'communication.center', 'icon' => 'bi-chat-dots', 'tables' => ['communication_channels'], 'permission' => 'communication.view'],
            ['module' => 'crm', 'label' => 'Clients', 'route' => 'clients.index', 'icon' => 'bi-people'],
            ['module' => 'projects', 'label' => 'Projects', 'route' => 'projects.index', 'icon' => 'bi-kanban', 'tables' => ['projects']],
            ['module' => 'core', 'label' => 'Products', 'route' => 'products.index', 'icon' => 'bi-box-seam', 'tables' => ['products']],
            ['module' => 'core', 'label' => 'Procurement', 'route' => 'erp.procurement', 'icon' => 'bi-truck', 'tables' => ['suppliers', 'purchase_orders', 'supplier_invoices']],
            ['module' => 'accounting', 'label' => 'Cost Accounting', 'route' => 'accounting.index', 'icon' => 'bi-diagram-3', 'tables' => ['departments', 'cost_centers', 'accounting_budgets']],
            ['module' => 'finance', 'label' => 'Finance', 'route' => 'finance.index', 'icon' => 'bi-bank', 'tables' => ['finance_accounts', 'journal_entries', 'journal_lines']],
            ['module' => 'documents', 'label' => 'Letters', 'route' => 'letters.index', 'icon' => 'bi-envelope-paper', 'tables' => ['letters', 'letter_templates']],
            ['module' => 'retail', 'label' => 'POS Orders', 'route' => 'pos-orders.index', 'icon' => 'bi-shop'],
            ['module' => 'hospitality', 'label' => 'Hospitality', 'route' => 'hospitality.dashboard', 'icon' => 'bi-cup-hot', 'tables' => ['hospitality_rooms', 'hospitality_reservations']],
            ['module' => 'fitness', 'label' => 'Fitness & Gym', 'route' => 'fitness.dashboard', 'icon' => 'bi-activity', 'tables' => ['fitness_members'], 'permission' => 'fitness.view'],
            ['module' => 'documents', 'label' => 'Quotations', 'route' => 'quotations.index', 'icon' => 'bi-file-earmark-text'],
            ['module' => 'documents', 'label' => 'Invoices', 'route' => 'invoices.index', 'icon' => 'bi-receipt'],
            ['module' => 'documents', 'label' => 'Receipts', 'route' => 'receipts.index', 'icon' => 'bi-cash-coin'],
            ['module' => 'administration', 'label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'bi-gear'],
            ['module' => 'administration', 'label' => 'Administration', 'route' => 'administration.index', 'icon' => 'bi-shield-lock', 'permission' => 'administration.view'],
        ]);

        return $this->nestFinanceItems($this->filterItems($items));
    }

    private function industrySidebar(): Collection
    {
        $tenant = ActiveTenant::current();
        $industry = $this->industrySlug($tenant?->industry);

        if (! $industry) {
            return collect();
        }

        $menus = $this->packageMenus($industry);

        if ($menus->isEmpty()) {
            return collect();
        }

        $items = $menus
            ->map(fn ($item) => [
                'module' => $industry,
                'label' => $item['label'] ?? null,
                'route' => $item['route'] ?? null,
                'icon' => $item['icon'] ?? 'bi-grid',
                'permission' => $item['permission'] ?? null,
                'permissions' => $item['permissions'] ?? null,
                'tables' => $item['tables'] ?? [],
                'active_routes' => $item['active_routes'] ?? [],
                'params' => $item['params'] ?? [],
                'section' => $item['section'] ?? null,
            ])
            ->filter(fn ($item) => $item['label'] && $item['route'])
            ->filter(fn ($item) => Route::has($item['route']) && $this->tablesReady($item['tables']))
            ->filter(fn ($item) => $this->allowed($item))
            ->values();

        if ($items->isEmpty()) {
            return collect();
        }

        $modules = app(ModuleRegistry::class);

        return $items
            ->when(
                ! $items->contains(fn ($item) => ($item['label'] ?? null) === 'Messaging')
                    && $this->itemAvailable(['module' => 'shared-communication', 'shared' => true, 'label' => 'Messaging', 'route' => 'communication.center', 'icon' => 'bi-chat-dots', 'tables' => ['communication_channels'], 'permission' => 'communication.view']),
                fn (Collection $items) => $items->push(['module' => 'shared-communication', 'shared' => true, 'label' => 'Messaging', 'route' => 'communication.center', 'icon' => 'bi-chat-dots', 'permission' => 'communication.view'])
            )
            ->when(
                ! $items->contains(fn ($item) => ($item['label'] ?? null) === 'Finance')
                    && Route::has('finance.index')
                    && $this->tablesReady(['journal_entries'])
                    && $modules->enabledSlug('finance'),
                fn (Collection $items) => $items->push(['module' => 'finance', 'label' => 'Finance', 'route' => 'finance.index', 'icon' => 'bi-bank'])
            )
            ->when(
                ! $items->contains(fn ($item) => ($item['label'] ?? null) === 'Settings'),
                fn (Collection $items) => $items->push(['module' => 'administration', 'label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'bi-gear'])
            )
            ->when(
                Route::has('administration.index') && $this->tablesReady(['iam_roles']) && auth()->check() && auth()->user()->hasPermission('administration.view'),
                fn (Collection $items) => $items->push(['module' => 'administration', 'label' => 'Administration', 'route' => 'administration.index', 'icon' => 'bi-shield-lock', 'permission' => 'administration.view'])
            )
            ->pipe(fn (Collection $items) => $this->nestFinanceItems($items))
            ->values();
    }

    private function nestFinanceItems(Collection $items): Collection
    {
        $taxItem = ['module' => 'etims-compliance', 'shared' => true, 'label' => 'Tax & ETIMS', 'route' => 'etims.dashboard', 'icon' => 'bi-receipt-cutoff', 'tables' => ['etims_submissions'], 'permission' => 'etims.view'];

        if (! $this->itemAvailable($taxItem)) {
            return $items->reject(fn ($item) => ($item['route'] ?? null) === 'etims.dashboard')->values();
        }

        $child = collect($taxItem)->except('tables')->all();

        return $items
            ->reject(fn ($item) => ($item['route'] ?? null) === 'etims.dashboard')
            ->map(function ($item) use ($child) {
                if (($item['route'] ?? null) !== 'finance.index') {
                    return $item;
                }

                $children = collect($item['children'] ?? [])
                    ->reject(fn ($childItem) => ($childItem['route'] ?? null) === 'etims.dashboard')
                    ->push($child)
                    ->values()
                    ->all();

                return array_merge($item, ['children' => $children]);
            })
            ->values();
    }

    private function packageMenus(string $industry): Collection
    {
        $package = base_path('Modules/'.Str::studly($industry).'/module.php');

        if (is_file($package)) {
            $definition = require $package;

            return collect($definition['menus'] ?? []);
        }

        $definition = app(IndustrySetupService::class)->find($industry);

        return collect($definition['menus'] ?? [])
            ->map(fn ($item) => [
                'label' => $item['label'] ?? null,
                'route' => $item['route'] ?? null,
                'icon' => $item['icon'] ?? 'bi-grid',
                'active_routes' => $item['active_routes'] ?? [],
                'params' => $item['params'] ?? [],
                'section' => $item['section'] ?? null,
            ]);
    }

    private function allowed(array $item): bool
    {
        $permissions = collect($item['permissions'] ?? [])
            ->when(! empty($item['permission']), fn (Collection $permissions) => $permissions->push($item['permission']))
            ->filter()
            ->values();

        if ($permissions->isEmpty()) {
            return true;
        }

        if (! auth()->check()) {
            return false;
        }

        return $permissions->contains(fn ($permission) => auth()->user()->hasPermission($permission));
    }

    private function filterItems(Collection $items): Collection
    {
        return $items
            ->filter(fn ($item) => $this->itemAvailable($item))
            ->values();
    }

    private function itemAvailable(array $item): bool
    {
        if (! Route::has($item['route']) || ! $this->tablesReady($item['tables'] ?? [])) {
            return false;
        }

        $moduleAllowed = ($item['module'] ?? null) === 'core'
            || ($item['shared'] ?? false)
            || app(ModuleRegistry::class)->enabledSlug($item['module']);

        return $moduleAllowed && $this->allowed($item);
    }

    private function tablesReady(array $tables): bool
    {
        return collect($tables)->every(fn ($table) => SchemaCache::hasTable($table));
    }

    private function industrySlug(?string $industry): ?string
    {
        if (! $industry) {
            return null;
        }

        $slug = Str::of($industry)->snake(' ')->slug('-')->toString();

        return $slug ?: null;
    }
}
