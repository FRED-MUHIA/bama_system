<?php

namespace Modules\Agriculture\Controllers;

use App\Http\Controllers\Controller;
use Modules\Agriculture\Services\AgricultureReportingService;

class AgricultureReportController extends Controller
{
    public function index()
    {
        return redirect()->route('agriculture.dashboard', ['section' => 'reports']);
    }

    public function csv(string $type, AgricultureReportingService $reports)
    {
        $rows = $reports->rows($type);
        abort_if($rows->isEmpty(), 404);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows->first()));
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);

        return response(stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=agriculture-{$type}.csv",
        ]);
    }
}
