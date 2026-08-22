<?php

namespace Modules\Automotive\Services;

class AutomotiveIndustryService
{
    public function definition(): array
    {
        return require base_path('Modules/Automotive/module.php');
    }

    public function enabledModules(?string $subIndustry): array
    {
        $definition = $this->definition();
        $sub = collect($definition['sub_industries'])->firstWhere('slug', $subIndustry ?: 'standard');

        return $sub['modules'] ?? $definition['modules'];
    }
}
