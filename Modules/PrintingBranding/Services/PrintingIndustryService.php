<?php

namespace Modules\PrintingBranding\Services;

use App\Services\IndustrySetupService;
use App\Support\ActiveTenant;

class PrintingIndustryService
{
    public const INDUSTRY = 'printing_branding';
    public const ROUTE_SLUG = 'printing-branding';

    public function definition(): array
    {
        return require base_path('Modules/PrintingBranding/module.php');
    }

    public function enabledModules(?string $subIndustry = null): array
    {
        $definition = $this->definition();
        $subIndustry ??= ActiveTenant::current()?->sub_industry ?? ActiveTenant::current()?->settings['sub_industry'] ?? null;
        $sub = collect($definition['sub_industries'] ?? [])->firstWhere('slug', $subIndustry);

        return $sub['modules'] ?? ($definition['modules'] ?? []);
    }

    public function dashboardProfile(): array
    {
        return app(IndustrySetupService::class)->dashboardFeatures(self::INDUSTRY, ActiveTenant::current()?->sub_industry);
    }

    public function suggestedChannels(): array
    {
        return $this->definition()['communication_channels'] ?? [];
    }
}
