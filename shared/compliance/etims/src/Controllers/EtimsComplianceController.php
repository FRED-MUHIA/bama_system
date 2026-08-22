<?php

namespace Shared\Compliance\Etims\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Shared\Compliance\Etims\Contracts\EtimsComplianceServiceContract;
use Shared\Compliance\Etims\Models\EtimsSubmission;

class EtimsComplianceController extends Controller
{
    public function index(Request $request, EtimsComplianceServiceContract $etims)
    {
        $industry = $request->query('industry', ActiveTenant::current()?->industry);
        $metrics = $etims->metrics($industry);
        $submissions = EtimsSubmission::query()
            ->when($industry, fn ($query) => $query->where('industry', $industry))
            ->latest()
            ->limit(50)
            ->get();

        $taxes = Schema::hasTable('tax_records')
            ? DB::table('tax_records')
                ->where('business_id', ActiveBusiness::id())
                ->latest('period_end')
                ->limit(25)
                ->get()
            : collect();

        $taxSummary = [
            'Output Tax' => $taxes->sum('output_amount'),
            'Input Tax' => $taxes->sum('input_amount'),
            'Payable Tax' => $taxes->sum('payable_amount'),
            'Draft Returns' => $taxes->where('status', 'Draft')->count(),
        ];

        return view('etims.dashboard', compact('industry', 'metrics', 'submissions', 'taxes', 'taxSummary'));
    }

    public function dashboard(Request $request, EtimsComplianceServiceContract $etims)
    {
        return response()->json([
            'data' => $etims->metrics($request->query('industry')),
        ]);
    }

    public function submissions(Request $request)
    {
        $submissions = EtimsSubmission::query()
            ->when($request->query('industry'), fn ($query, $industry) => $query->where('industry', $industry))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->limit((int) $request->query('limit', 50))
            ->get();

        return response()->json(['data' => $submissions]);
    }

    public function retry(Request $request, EtimsComplianceServiceContract $etims)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return response()->json([
            'data' => $etims->retryPending($data['limit'] ?? 50)->values(),
        ]);
    }

    public function retryWeb(Request $request, EtimsComplianceServiceContract $etims)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $retried = $etims->retryPending($data['limit'] ?? 50);

        return back()->with('status', $retried->count().' ETIMS submission(s) retried.');
    }
}
