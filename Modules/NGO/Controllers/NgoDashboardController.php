<?php

namespace Modules\NGO\Controllers;

use App\Http\Controllers\Controller;
use Modules\NGO\Models\NgoActivity;
use Modules\NGO\Models\NgoProgram;
use Modules\NGO\Services\NgoService;

class NgoDashboardController extends Controller
{
    public function __invoke(NgoService $ngo)
    {
        abort_unless(auth()->user()?->hasPermission('ngo.view'), 403);

        return view('ngo.dashboard', [
            'tenant' => auth()->user()?->currentTenant,
            'metrics' => $ngo->dashboard(),
            'programs' => $ngo->programPerformance(),
            'upcomingActivities' => NgoActivity::with('program')->whereIn('status', ['Planned', 'In Progress'])->whereDate('starts_on', '>=', today())->orderBy('starts_on')->limit(8)->get(),
            'projects' => \App\Models\Project::orderByDesc('updated_at')->limit(8)->get(),
        ]);
    }
}
