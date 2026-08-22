<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use Modules\Hospitality\Services\HospitalityReportService;

class HospitalityReportController extends Controller
{
    public function index(HospitalityReportService $reports)
    {
        return view('hospitality.index', [
            'title' => 'Reports',
            'section' => 'reports',
            'reports' => $reports->reports(),
        ]);
    }

    public function live(HospitalityReportService $reports)
    {
        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'reports' => $reports->reports(),
        ]);
    }
}
