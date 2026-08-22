<?php

namespace Modules\RealEstate\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\RealEstate\Models\Agent;
use Modules\RealEstate\Models\Buyer;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\MaintenanceRequest;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Repositories\RealEstateRepository;
use Modules\RealEstate\Services\RealEstateUtilityBillingService;
use Modules\RealEstate\Services\RealEstateService;
use Modules\RealEstate\Services\TenantLedgerService;
use Modules\RealEstate\Services\TenantOffboardingService;

class RealEstateApiController extends Controller
{
    public function dashboard(RealEstateRepository $repository)
    {
        return response()->json(['data' => $repository->metrics()]);
    }

    public function listings()
    {
        return response()->json(['data' => Listing::with('property', 'unit', 'agent')->where('public_ready', true)->latest()->paginate(20)]);
    }

    public function tenantPortal(Tenant $tenant)
    {
        return response()->json(['data' => $tenant->load('client', 'leases.property', 'leases.unit', 'leases.charges.invoice', 'utilityBills.invoice', 'amenityBookings.amenity', 'statements', 'maintenanceRequests', 'serviceRequests')]);
    }

    public function buyerPortal(Buyer $buyer)
    {
        return response()->json(['data' => $buyer->load('client', 'sales.property', 'sales.unit', 'sales.invoice')]);
    }

    public function agentPortal(Agent $agent)
    {
        return response()->json(['data' => $agent->load('listings.property', 'sales.property', 'commissions')]);
    }

    public function serviceRequest(Request $request, RealEstateService $service)
    {
        $data = $request->validate([
            'real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_tenant_id' => ['nullable', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'request_type' => ['required', 'in:Plumbing Issues,Electrical Issues,Security Issues,Cleaning Requests,Repairs'],
            'description' => ['required', 'string'],
            'status' => ['nullable', 'in:Open,Assigned,In Progress,Resolved,Closed'],
        ]);

        $data['status'] ??= 'Open';
        $record = $service->createServiceRequest($data);

        return response()->json(['data' => $record], 201);
    }

    public function lease(Lease $lease)
    {
        return response()->json(['data' => $lease->load('tenant.client', 'property', 'unit', 'charges.invoice', 'utilityBills.invoice')]);
    }

    public function tenantLedger(Tenant $tenant, Request $request, TenantLedgerService $ledger)
    {
        return response()->json(['data' => $ledger->ledger($tenant, $request->query('start'), $request->query('end'))]);
    }

    public function tenantPayments(Tenant $tenant, TenantLedgerService $ledger)
    {
        $ledger->syncPayments($tenant);
        $invoiceIds = $tenant->ledgerEntries()->whereNotNull('invoice_id')->pluck('invoice_id')->unique();

        return response()->json(['data' => Payment::with('invoice', 'receipt', 'paymentMethod')->whereIn('invoice_id', $invoiceIds)->latest('payment_date')->get()]);
    }

    public function tenantStatements(Tenant $tenant)
    {
        return response()->json(['data' => $tenant->statements()->latest()->get()]);
    }

    public function tenantUtilities(Tenant $tenant)
    {
        return response()->json(['data' => [
            'meters' => $tenant->utilityMeters()->with('utilityType', 'property', 'unit')->get(),
            'bills' => $tenant->utilityBills()->with('utilityType', 'invoice')->latest()->get(),
        ]]);
    }

    public function tenantArchive()
    {
        return response()->json(['data' => Tenant::with('client', 'leases.property', 'leases.unit')->archivedLifecycle()->latest()->paginate(25)]);
    }

    public function tenantOffboarding(Tenant $tenant)
    {
        return response()->json(['data' => $tenant->load('client', 'leases.property', 'leases.unit', 'utilityBills', 'maintenanceRequests', 'serviceRequests')]);
    }

    public function startTenantNotice(Tenant $tenant, Request $request, TenantOffboardingService $offboarding)
    {
        return response()->json(['data' => $offboarding->startNotice($tenant, $request->validate([
            'notice_date' => ['nullable', 'date'],
            'move_out_date' => ['nullable', 'date', 'after_or_equal:notice_date'],
            'offboarding_notes' => ['nullable', 'string'],
        ]))]);
    }

    public function progressTenantExit(Tenant $tenant, Request $request, TenantOffboardingService $offboarding)
    {
        $data = $request->validate([
            'step' => ['required', Rule::in(TenantOffboardingService::STEPS)],
            'termination_date' => ['nullable', 'date'],
            'final_inspection_date' => ['nullable', 'date'],
            'move_out_date' => ['nullable', 'date'],
            'offboarding_notes' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => $offboarding->progress($tenant, $data['step'], $data)]);
    }

    public function archiveTenant(Tenant $tenant, Request $request, TenantOffboardingService $offboarding)
    {
        return response()->json(['data' => $offboarding->archive($tenant, $request->validate([
            'move_out_date' => ['nullable', 'date'],
            'offboarding_notes' => ['nullable', 'string'],
        ]))]);
    }

    public function restoreTenant(Tenant $tenant, TenantOffboardingService $offboarding)
    {
        return response()->json(['data' => $offboarding->restore($tenant)]);
    }

    public function utilityReading(Request $request, RealEstateUtilityBillingService $service)
    {
        $reading = $service->recordReading($request->validate([
            'real_estate_utility_meter_id' => ['required', Rule::exists('real_estate_utility_meters', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_tenant_id' => ['nullable', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'previous_reading' => ['nullable', 'numeric', 'min:0'],
            'current_reading' => ['required', 'numeric', 'min:0'],
            'reading_date' => ['required', 'date'],
            'rate_per_unit' => ['nullable', 'numeric', 'min:0'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'source' => ['nullable', 'in:Manual Entry,Bulk Upload,Smart Meter API'],
        ]));

        return response()->json(['data' => $reading->load('bill.invoice')], 201);
    }

    public function utilityBill(Request $request, RealEstateUtilityBillingService $service)
    {
        $bill = $service->createUtilityBill($request->validate([
            'real_estate_tenant_id' => ['required', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_utility_type_id' => ['required', Rule::exists('real_estate_utility_types', 'id')->where('business_id', ActiveBusiness::id())],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'rate_per_unit' => ['nullable', 'numeric', 'min:0'],
            'fixed_charge' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]));

        return response()->json(['data' => $bill->load('invoice', 'utilityType')], 201);
    }

    public function reportExport(string $type, RealEstateRepository $repository, TenantLedgerService $ledger)
    {
        $rows = $repository->exportRows($type, $ledger);

        $handle = fopen('php://temp', 'r+');
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
