<?php

namespace Modules\Chama\Controllers;

use App\Http\Controllers\Controller;
use Modules\Chama\Models\Ballot;
use Modules\Chama\Models\ChamaDocument;
use Modules\Chama\Models\ChamaRule;
use Modules\Chama\Models\Contribution;
use Modules\Chama\Models\ContributionSchedule;
use Modules\Chama\Models\ContributionType;
use Modules\Chama\Models\DividendRun;
use Modules\Chama\Models\Fine;
use Modules\Chama\Models\Investment;
use Modules\Chama\Models\Loan;
use Modules\Chama\Models\LoanProduct;
use Modules\Chama\Models\Meeting;
use Modules\Chama\Models\Member;
use Modules\Chama\Models\MerryGoRoundCycle;
use Modules\Chama\Models\MerryGoRoundRound;
use Modules\Chama\Models\SavingsAccount;
use Modules\Chama\Models\ShareTransaction;
use Modules\Chama\Models\TableBankingSession;
use Modules\Chama\Models\WelfareRequest;
use Modules\Chama\Services\ChamaDashboardService;
use Modules\Chama\Services\ChamaService;

class ChamaDashboardController extends Controller
{
    public function __invoke(ChamaDashboardService $dashboard, ChamaService $chama)
    {
        $subIndustry = $chama->subIndustry();

        return view('chama.dashboard', [
            'subIndustryName' => $subIndustry['name'] ?? 'Chama Management',
            'activeModules' => $chama->activeModules(),
            'metrics' => $dashboard->metrics(),
            'alerts' => $dashboard->alerts(),
            'charts' => $dashboard->charts(),
            'members' => Member::latest()->limit(80)->get(),
            'applications' => Member::where('status', 'Applicant')->latest()->limit(30)->get(),
            'contributionTypes' => ContributionType::latest()->limit(60)->get(),
            'schedules' => ContributionSchedule::with('member', 'contributionType')->latest('due_date')->limit(80)->get(),
            'contributions' => Contribution::with('member', 'contributionType')->latest('payment_date')->limit(80)->get(),
            'savingsAccounts' => SavingsAccount::with('member')->latest()->limit(80)->get(),
            'cycles' => MerryGoRoundCycle::with('members.member')->latest()->limit(40)->get(),
            'rounds' => MerryGoRoundRound::with('cycle', 'beneficiary')->latest()->limit(60)->get(),
            'tableSessions' => TableBankingSession::latest('session_date')->limit(50)->get(),
            'loanProducts' => LoanProduct::latest()->limit(40)->get(),
            'loans' => Loan::with('member', 'product', 'schedules')->latest()->limit(80)->get(),
            'fines' => Fine::with('member')->latest('fine_date')->limit(80)->get(),
            'welfareRequests' => WelfareRequest::with('member')->latest()->limit(60)->get(),
            'shareTransactions' => ShareTransaction::with('member')->latest('transaction_date')->limit(80)->get(),
            'investments' => Investment::latest()->limit(50)->get(),
            'dividendRuns' => DividendRun::withCount('allocations')->latest()->limit(40)->get(),
            'meetings' => Meeting::withCount('attendance')->latest('meeting_date')->limit(50)->get(),
            'ballots' => Ballot::withCount('votes')->latest()->limit(40)->get(),
            'rules' => ChamaRule::latest()->limit(20)->get(),
            'documents' => ChamaDocument::with('member')->latest()->limit(50)->get(),
            'users' => $this->activeBusinessUsers()->orderBy('name')->get(),
        ]);
    }
}
