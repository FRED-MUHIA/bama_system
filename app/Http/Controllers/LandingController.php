<?php

namespace App\Http\Controllers;

use App\Models\MarketingPage;
use App\Services\IndustrySetupService;
use App\Services\PlanSelectionService;

class LandingController extends Controller
{
    public function __invoke(IndustrySetupService $industries, PlanSelectionService $plans)
    {
        $page = MarketingPage::resolve('home');

        return view('landing.index', [
            'industries' => $industries->implementedIndustries(),
            'plans' => $plans->all(),
            'marketingPage' => $page,
            'marketingContent' => $page->sections ?: MarketingPage::defaultSections('home'),
        ]);
    }

    public function industry(string $industry, IndustrySetupService $industries, PlanSelectionService $plans)
    {
        abort_unless($industries->isImplemented($industry), 404);

        $definition = $industries->find($industry);

        return view('landing.industry', [
            'industry' => $definition + [
                'industry' => $definition['name'],
                'dashboard' => $industries->dashboardFeatures($definition['slug']),
            ],
            'industries' => $industries->implementedIndustries(),
            'plans' => $plans->all(),
        ]);
    }
}
