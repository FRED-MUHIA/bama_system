<?php

namespace Modules\RealEstate\Repositories;

use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\MaintenanceRequest;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\RentalCharge;
use Modules\RealEstate\Models\Sale;
use Modules\RealEstate\Models\ServiceRequest;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\TenantLedger;
use Modules\RealEstate\Models\UtilityBill;
use Modules\RealEstate\Models\UtilityConsumption;
use Modules\RealEstate\Models\Unit;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\Commission;
use Modules\RealEstate\Models\DevelopmentProject;
use Modules\RealEstate\Models\Inspection;
use Modules\RealEstate\Models\LandParcel;
use Modules\RealEstate\Models\Valuation;
use Modules\RealEstate\Services\TenantLedgerService;

class RealEstateRepository
{
    public function metrics(): array
    {
        $properties = Property::query();
        $units = Unit::query();
        $leases = Lease::query();
        $sales = Sale::query();
        $maintenance = MaintenanceRequest::query();

        $totalUnits = (clone $units)->count();
        $occupiedUnits = (clone $units)->where('occupancy_status', 'Occupied')->count();

        return [
            'properties' => (clone $properties)->count(),
            'portfolio_value' => (float) (clone $properties)->sum('market_value'),
            'units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => max($totalUnits - $occupiedUnits, 0),
            'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0,
            'active_leases' => (clone $leases)->where('status', 'Active')->count(),
            'available_listings' => Listing::whereIn('status', ['Approved', 'Published'])->count(),
            'outstanding_rent' => (float) RentalCharge::whereIn('status', ['Outstanding', 'Partial', 'Overdue'])->sum('amount'),
            'sales_pipeline' => (float) (clone $sales)->whereIn('status', ['Reserved', 'Installment', 'Agreement'])->sum('balance'),
            'closed_sales' => (clone $sales)->where('status', 'Completed')->count(),
            'open_maintenance' => (clone $maintenance)->whereIn('status', ['Open', 'Assigned', 'In Progress'])->count(),
            'maintenance_costs' => (float) (clone $maintenance)->sum('actual_cost'),
            'utility_revenue' => (float) UtilityBill::sum('amount'),
            'amenity_revenue' => (float) AmenityBooking::sum('charge_amount'),
            'water_consumption' => (float) UtilityConsumption::whereHas('utilityType', fn ($query) => $query->where('name', 'like', '%water%'))->sum('quantity'),
            'electricity_consumption' => (float) UtilityConsumption::whereHas('utilityType', fn ($query) => $query->where('name', 'like', '%electric%'))->sum('quantity'),
            'outstanding_balances' => (float) TenantLedger::sum(\DB::raw('debit - credit')),
        ];
    }

    public function portfolioRows()
    {
        return Property::withCount(['units', 'listings', 'leases', 'maintenanceRequests'])
            ->with('branch', 'manager')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function exportRows(string $type, TenantLedgerService $ledger)
    {
        return match ($type) {
            'tenant-payments' => Tenant::with('client', 'ledgerEntries.payment.receipt', 'ledgerEntries.payment.paymentMethod', 'ledgerEntries.property', 'ledgerEntries.unit')
                ->get()
                ->flatMap(function (Tenant $tenant) use ($ledger) {
                    $ledger->syncPayments($tenant);

                    return $tenant->ledgerEntries()
                        ->with('payment.receipt', 'payment.paymentMethod', 'property', 'unit')
                        ->where('entry_type', 'Payment')
                        ->get()
                        ->map(fn ($entry) => [
                            'receipt_number' => $entry->payment?->receipt?->receipt_number,
                            'date' => $entry->entry_date?->toDateString(),
                            'tenant' => $tenant->client?->name,
                            'property' => $entry->property?->property_name,
                            'unit' => $entry->unit?->unit_number,
                            'payment_type' => $entry->payment?->paymentMethod?->name,
                            'amount' => $entry->credit,
                            'balance' => $entry->running_balance,
                        ]);
                }),
            'tenant-ledger' => TenantLedger::with('tenant.client', 'property', 'unit')->orderBy('entry_date')->get()->map(fn ($entry) => [
                'date' => $entry->entry_date?->toDateString(),
                'tenant' => $entry->tenant?->client?->name,
                'property' => $entry->property?->property_name,
                'unit' => $entry->unit?->unit_number,
                'type' => $entry->entry_type,
                'description' => $entry->description,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $entry->running_balance,
            ]),
            'utility-billing' => UtilityBill::with('tenant.client', 'unit', 'utilityType')->latest()->get()->map(fn ($bill) => [
                'tenant' => $bill->tenant?->client?->name,
                'unit' => $bill->unit?->unit_number,
                'utility' => $bill->utilityType?->name,
                'previous_reading' => $bill->meterReading?->previous_reading,
                'current_reading' => $bill->meterReading?->current_reading,
                'consumption' => $bill->quantity,
                'charge' => $bill->amount,
            ]),
            'lease-billing' => Lease::with('tenant.client', 'unit', 'utilityBills')->get()->map(fn ($lease) => [
                'tenant' => $lease->tenant?->client?->name,
                'unit' => $lease->unit?->unit_number,
                'rent' => $lease->rent_amount,
                'utilities' => $lease->utilityBills->sum('amount'),
                'service_charges' => $lease->charges->where('charge_type', 'Service Charge')->sum('amount'),
                'total_due' => $lease->rent_amount + $lease->utilityBills->sum('amount') + $lease->charges->where('charge_type', 'Service Charge')->sum('amount'),
            ]),
            'outstanding-balances' => Tenant::with('client', 'leases.property', 'leases.unit')->get()->map(function (Tenant $tenant) use ($ledger) {
                $ledger->syncPayments($tenant);
                $balance = (float) $tenant->ledgerEntries()->sum(\DB::raw('debit - credit'));
                $oldest = $tenant->ledgerEntries()->where('debit', '>', 0)->oldest('entry_date')->first();

                return [
                    'tenant' => $tenant->client?->name,
                    'property' => $tenant->leases->first()?->property?->property_name,
                    'unit' => $tenant->leases->first()?->unit?->unit_number,
                    'outstanding_amount' => $balance,
                    'days_outstanding' => $oldest ? now()->diffInDays($oldest->entry_date) : 0,
                ];
            })->filter(fn ($row) => $row['outstanding_amount'] > 0)->values(),
            'move-outs' => Tenant::with('client', 'leases.property', 'leases.unit')->whereIn('status', ['Notice Given', 'Moving Out', 'Moved Out', 'Archived'])->latest()->get()->map(fn ($tenant) => [
                'tenant_number' => $tenant->tenant_number,
                'tenant' => $tenant->client?->name,
                'status' => $tenant->status,
                'notice_date' => $tenant->notice_date?->toDateString(),
                'move_out_date' => $tenant->move_out_date?->toDateString(),
                'offboarding_step' => $tenant->offboarding_step,
                'property' => $tenant->leases->first()?->property?->property_name,
                'unit' => $tenant->leases->first()?->unit?->unit_number,
            ]),
            'archived-tenants' => Tenant::with('client', 'leases.property', 'leases.unit')->archivedLifecycle()->latest()->get()->map(fn ($tenant) => [
                'tenant_number' => $tenant->tenant_number,
                'tenant' => $tenant->client?->name,
                'status' => $tenant->status,
                'archived_at' => $tenant->archived_at?->toDateTimeString(),
                'move_out_date' => $tenant->move_out_date?->toDateString(),
                'leases' => $tenant->leases->count(),
            ]),
            'tenant-history' => Tenant::with('client', 'leases.property', 'leases.unit', 'utilityBills', 'maintenanceRequests')->latest()->get()->map(fn ($tenant) => [
                'tenant_number' => $tenant->tenant_number,
                'tenant' => $tenant->client?->name,
                'status' => $tenant->status,
                'lease_records' => $tenant->leases->count(),
                'utility_bills' => $tenant->utilityBills->count(),
                'maintenance_records' => $tenant->maintenanceRequests->count(),
                'archived_at' => $tenant->archived_at?->toDateTimeString(),
            ]),
            'vacancy' => Unit::with('property')->where('occupancy_status', 'Vacant')->get()->map(fn ($unit) => [
                'property' => $unit->property?->property_name,
                'unit_number' => $unit->unit_number,
                'unit_type' => $unit->unit_type,
                'rent_amount' => $unit->rent_amount,
                'sale_price' => $unit->sale_price,
                'status' => $unit->occupancy_status,
            ]),
            'commissions' => Commission::with('agent')->get()->map(fn ($commission) => [
                'commission_number' => $commission->commission_number,
                'agent' => $commission->agent?->name,
                'type' => $commission->commission_type,
                'earned_amount' => $commission->earned_amount,
                'paid_amount' => $commission->paid_amount,
                'status' => $commission->status,
            ]),
            'maintenance' => MaintenanceRequest::with('property', 'unit', 'tenant.client')->get()->map(fn ($request) => [
                'request_number' => $request->request_number,
                'property' => $request->property?->property_name,
                'unit' => $request->unit?->unit_number,
                'tenant' => $request->tenant?->client?->name,
                'priority' => $request->priority,
                'estimated_cost' => $request->estimated_cost,
                'actual_cost' => $request->actual_cost,
                'status' => $request->status,
            ]),
            'service-requests' => ServiceRequest::with('tenant.client', 'property', 'unit', 'assignee')->get()->map(fn ($request) => [
                'request_number' => $request->request_number,
                'tenant' => $request->tenant?->client?->name,
                'property' => $request->property?->property_name,
                'unit' => $request->unit?->unit_number,
                'request_type' => $request->request_type,
                'assigned_to' => $request->assignee?->name,
                'status' => $request->status,
            ]),
            'inspections' => Inspection::with('property', 'unit', 'inspector')->get()->map(fn ($inspection) => [
                'inspection_number' => $inspection->inspection_number,
                'property' => $inspection->property?->property_name,
                'unit' => $inspection->unit?->unit_number,
                'inspector' => $inspection->inspector?->name,
                'inspection_type' => $inspection->inspection_type,
                'inspection_date' => $inspection->inspection_date?->toDateString(),
                'status' => $inspection->status,
            ]),
            'valuations' => Valuation::with('property', 'valuer')->get()->map(fn ($valuation) => [
                'property' => $valuation->property?->property_name,
                'valuer' => $valuation->valuer?->name,
                'valuation_date' => $valuation->valuation_date?->toDateString(),
                'market_value' => $valuation->market_value,
                'rental_value' => $valuation->rental_value,
                'status' => $valuation->status,
            ]),
            'land' => LandParcel::with('property')->get()->map(fn ($parcel) => [
                'parcel_number' => $parcel->parcel_number,
                'title_number' => $parcel->title_number,
                'property' => $parcel->property?->property_name,
                'land_size' => $parcel->land_size,
                'land_size_unit' => $parcel->land_size_unit,
                'zoning' => $parcel->zoning,
                'ownership_status' => $parcel->ownership_status,
            ]),
            'development' => DevelopmentProject::with('property', 'sharedProject')->get()->map(fn ($project) => [
                'development_number' => $project->development_number,
                'name' => $project->name,
                'property' => $project->property?->property_name,
                'shared_project' => $project->sharedProject?->name,
                'phase' => $project->phase,
                'budget' => $project->budget,
                'actual_cost' => $project->actual_cost,
                'progress_percent' => $project->progress_percent,
                'status' => $project->status,
            ]),
            default => collect(),
        };
    }
}
