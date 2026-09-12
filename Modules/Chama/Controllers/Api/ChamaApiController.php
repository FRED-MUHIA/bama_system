<?php

namespace Modules\Chama\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Chama\Models\Member;
use Modules\Chama\Services\ChamaDashboardService;
use Modules\Chama\Services\MemberService;

class ChamaApiController extends Controller
{
    public function dashboard(ChamaDashboardService $dashboard)
    {
        return response()->json([
            'metrics' => $dashboard->metrics(),
            'alerts' => $dashboard->alerts(),
            'charts' => $dashboard->charts(),
        ]);
    }

    public function statement(Member $member, MemberService $members)
    {
        return response()->json($members->statement($member));
    }
}
