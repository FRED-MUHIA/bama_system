<?php

namespace Modules\RealEstate\Controllers;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Models\Commission;
use Modules\RealEstate\Models\DevelopmentProject;
use Modules\RealEstate\Models\Inspection;
use Modules\RealEstate\Models\LandParcel;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\MaintenanceRequest;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\Sale;
use Modules\RealEstate\Models\ServiceRequest;
use Modules\RealEstate\Models\TenantLedger;
use Modules\RealEstate\Models\TenantStatement;
use Modules\RealEstate\Models\UtilityBill;
use Modules\RealEstate\Models\Unit;
use Modules\RealEstate\Models\Valuation;
use Modules\RealEstate\Repositories\RealEstateRepository;
use Modules\RealEstate\Services\TenantLedgerService;

class RealEstateReportController extends Controller
{
    public function index(RealEstateRepository $repository)
    {
        return response()->view('real-estate.reports', [
            'metrics' => $repository->metrics(),
            'properties' => Property::withCount('units')->get(),
            'units' => Unit::with('property')->get(),
            'leases' => Lease::with('tenant.client', 'property', 'unit')->get(),
            'sales' => Sale::with('buyer.client', 'property', 'agent')->get(),
            'commissions' => Commission::with('agent')->get(),
            'maintenance' => MaintenanceRequest::with('property', 'unit')->get(),
            'utilityBills' => UtilityBill::with('tenant.client', 'utilityType', 'unit')->get(),
            'ledgerRows' => TenantLedger::with('tenant.client', 'property', 'unit')->latest()->limit(100)->get(),
            'statements' => TenantStatement::with('tenant.client')->latest()->limit(50)->get(),
        ]);
    }

    public function csv(string $type, RealEstateRepository $repository, TenantLedgerService $ledger)
    {
        $rows = match ($type) {
            'properties' => Property::select('property_code', 'property_name', 'property_type', 'status', 'city', 'market_value')->get(),
            'units' => Unit::with('property')->get()->map(fn ($unit) => ['property' => $unit->property?->property_name, 'unit_number' => $unit->unit_number, 'status' => $unit->occupancy_status, 'rent_amount' => $unit->rent_amount, 'sale_price' => $unit->sale_price]),
            'leases' => Lease::with('tenant.client', 'property', 'unit')->get()->map(fn ($lease) => ['lease_number' => $lease->lease_number, 'tenant' => $lease->tenant?->client?->name, 'property' => $lease->property?->property_name, 'unit' => $lease->unit?->unit_number, 'rent_amount' => $lease->rent_amount, 'status' => $lease->status]),
            'sales' => Sale::with('buyer.client', 'property', 'agent')->get()->map(fn ($sale) => ['sale_number' => $sale->sale_number, 'buyer' => $sale->buyer?->client?->name, 'property' => $sale->property?->property_name, 'agent' => $sale->agent?->name, 'sale_price' => $sale->sale_price, 'balance' => $sale->balance, 'status' => $sale->status]),
            'commissions' => Commission::with('agent')->get()->map(fn ($commission) => ['commission_number' => $commission->commission_number, 'agent' => $commission->agent?->name, 'type' => $commission->commission_type, 'earned_amount' => $commission->earned_amount, 'paid_amount' => $commission->paid_amount, 'status' => $commission->status]),
            'maintenance' => MaintenanceRequest::with('property', 'unit', 'tenant.client')->get()->map(fn ($request) => ['request_number' => $request->request_number, 'property' => $request->property?->property_name, 'unit' => $request->unit?->unit_number, 'tenant' => $request->tenant?->client?->name, 'priority' => $request->priority, 'estimated_cost' => $request->estimated_cost, 'actual_cost' => $request->actual_cost, 'status' => $request->status]),
            'service-requests' => ServiceRequest::with('tenant.client', 'property', 'unit', 'assignee')->get()->map(fn ($request) => ['request_number' => $request->request_number, 'tenant' => $request->tenant?->client?->name, 'property' => $request->property?->property_name, 'unit' => $request->unit?->unit_number, 'request_type' => $request->request_type, 'assigned_to' => $request->assignee?->name, 'status' => $request->status]),
            'inspections' => Inspection::with('property', 'unit', 'inspector')->get()->map(fn ($inspection) => ['inspection_number' => $inspection->inspection_number, 'property' => $inspection->property?->property_name, 'unit' => $inspection->unit?->unit_number, 'inspector' => $inspection->inspector?->name, 'inspection_type' => $inspection->inspection_type, 'inspection_date' => $inspection->inspection_date?->toDateString(), 'status' => $inspection->status]),
            'valuations' => Valuation::with('property', 'valuer')->get()->map(fn ($valuation) => ['property' => $valuation->property?->property_name, 'valuer' => $valuation->valuer?->name, 'valuation_date' => $valuation->valuation_date?->toDateString(), 'market_value' => $valuation->market_value, 'rental_value' => $valuation->rental_value, 'status' => $valuation->status]),
            'land' => LandParcel::with('property')->get()->map(fn ($parcel) => ['parcel_number' => $parcel->parcel_number, 'title_number' => $parcel->title_number, 'property' => $parcel->property?->property_name, 'land_size' => $parcel->land_size, 'land_size_unit' => $parcel->land_size_unit, 'zoning' => $parcel->zoning, 'ownership_status' => $parcel->ownership_status]),
            'development' => DevelopmentProject::with('property', 'sharedProject')->get()->map(fn ($project) => ['development_number' => $project->development_number, 'name' => $project->name, 'property' => $project->property?->property_name, 'shared_project' => $project->sharedProject?->name, 'phase' => $project->phase, 'budget' => $project->budget, 'actual_cost' => $project->actual_cost, 'progress_percent' => $project->progress_percent, 'status' => $project->status]),
            'tenant-payments', 'tenant-ledger', 'utility-billing', 'lease-billing', 'outstanding-balances', 'move-outs', 'archived-tenants', 'tenant-history', 'vacancy' => $repository->exportRows($type, $ledger),
            default => abort(404),
        };

        $handle = fopen('php://temp', 'r+');
        $rows = $rows->map(fn ($row) => collect((array) $row)->map(fn ($value) => is_array($value) || is_object($value) ? json_encode($value) : $value)->all());

        if ($rows->isNotEmpty()) {
            fputcsv($handle, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        }
        rewind($handle);

        return response(stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=real-estate-{$type}.csv",
        ]);
    }
}
