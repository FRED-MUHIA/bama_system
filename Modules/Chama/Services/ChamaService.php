<?php

namespace Modules\Chama\Services;

use App\Support\ActiveTenant;

class ChamaService
{
    public function definition(): array
    {
        return require base_path('Modules/Chama/module.php');
    }

    public function subIndustry(?string $slug = null): array
    {
        $definition = $this->definition();
        $slug ??= ActiveTenant::current()?->sub_industry;

        return collect($definition['sub_industries'] ?? [])->firstWhere('slug', $slug)
            ?? collect($definition['sub_industries'] ?? [])->first()
            ?? [];
    }

    public function activeModules(?string $slug = null): array
    {
        $definition = $this->definition();
        $subIndustry = $this->subIndustry($slug);

        return $subIndustry['modules'] ?? $definition['modules'] ?? [];
    }

    public function configuration(?string $slug = null): array
    {
        $subIndustry = $this->subIndustry($slug);
        $modules = $this->activeModules($slug);

        return [
            'sub_industry' => $slug ?? ActiveTenant::current()?->sub_industry,
            'enabled_modules' => $modules,
            'merry_go_round_enabled' => in_array('Merry-Go-Round', $modules, true),
            'table_banking_enabled' => in_array('Table Banking', $modules, true),
            'investment_enabled' => in_array('Investment Management', $modules, true) || in_array('Share Capital', $modules, true),
            'welfare_enabled' => in_array('Welfare Fund', $modules, true) || in_array('Claims', $modules, true),
            'enterprise_controls_enabled' => str_contains(strtolower((string) ($subIndustry['name'] ?? '')), 'enterprise'),
            'multi_branch_enabled' => ($subIndustry['slug'] ?? null) === 'multi-branch',
            'finance_integration' => true,
            'payment_allocation' => true,
            'member_portal_ready' => true,
        ];
    }
}
