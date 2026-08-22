<?php

namespace Modules\Fitness\Services;

use App\Models\Payment;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;

class FitnessDashboardService
{
    public function metrics(): array
    {
        return [
            'Total Members' => Member::count(),
            'Active Members' => Member::where('status', 'Active')->count(),
            'New Members' => Member::whereDate('join_date', '>=', now()->startOfMonth())->count(),
            'Expiring This Week' => MemberMembership::where('status', 'Active')->whereBetween('ends_at', [today(), today()->addDays(7)])->count(),
            'Revenue MTD' => Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->where('payable_type', 'like', '%Fitness%')->sum('amount'),
            'Outstanding Balances' => MemberMembership::whereIn('status', ['Active', 'Pending'])->sum('balance'),
        ];
    }

    public function counts(): array
    {
        return [
            'plans' => MembershipPlan::count(),
            'memberships' => MemberMembership::count(),
            'pending' => Member::where('status', 'Pending')->count(),
        ];
    }
}
