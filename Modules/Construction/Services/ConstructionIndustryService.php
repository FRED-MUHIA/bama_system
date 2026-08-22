<?php

namespace Modules\Construction\Services;

use App\Services\IndustrySetupService;
use App\Support\ActiveTenant;

class ConstructionIndustryService
{
    public const INDUSTRY = 'construction';

    public function definition(): array
    {
        return require base_path('Modules/Construction/module.php');
    }

    public function enabledModules(?string $subIndustry = null): array
    {
        $definition = $this->definition();
        $subIndustry ??= ActiveTenant::current()?->sub_industry ?? ActiveTenant::current()?->settings['sub_industry'] ?? null;
        $sub = collect($definition['sub_industries'] ?? [])->firstWhere('slug', $subIndustry);

        return $sub['modules'] ?? $definition['modules'] ?? $definition['features'] ?? [];
    }

    public function dashboardProfile(): array
    {
        return app(IndustrySetupService::class)->dashboardFeatures(self::INDUSTRY, ActiveTenant::current()?->sub_industry);
    }
}
