<?php

namespace App\Http\Controllers;

use App\Services\IndustrySetupService;
use App\Services\PlanSelectionService;

class LandingController extends Controller
{
    public function __invoke(IndustrySetupService $industries, PlanSelectionService $plans)
    {
        return view('landing.index', [
            'industries' => $industries->implementedIndustries(),
            'plans' => $plans->all(),
        ]);
    }
}
