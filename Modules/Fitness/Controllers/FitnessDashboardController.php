<?php

namespace Modules\Fitness\Controllers;

use App\Http\Controllers\Controller;
use App\Services\IndustrySetupService;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;
use Modules\Fitness\Services\FitnessDashboardService;
use Modules\Fitness\Services\FitnessFeatureGate;

class FitnessDashboardController extends Controller
{
    public function __invoke(FitnessDashboardService $dashboard, FitnessFeatureGate $gate)
    {
        $gate->authorize('memberships');
        $tenant = auth()->user()?->currentTenant;

        return view('fitness.dashboard', [
            'tenant' => $tenant,
            'industryDashboard' => app(IndustrySetupService::class)->dashboardFeaturesForTenant($tenant),
            'metrics' => $dashboard->metrics(),
            'counts' => $dashboard->counts(),
            'plans' => MembershipPlan::latest()->limit(5)->get(),
            'members' => Member::with('client', 'assignedTrainer', 'activeMembership.plan')->latest()->limit(8)->get(),
            'expiring' => MemberMembership::with('member.client', 'plan')->where('status', 'Active')->whereBetween('ends_at', [today(), today()->addDays(7)])->orderBy('ends_at')->limit(8)->get(),
        ]);
    }
}
