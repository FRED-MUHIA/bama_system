<?php

namespace Modules\RealEstate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\RealEstate\Models\Agent;
use Modules\RealEstate\Models\DevelopmentProject;
use Modules\RealEstate\Models\Inspection;
use Modules\RealEstate\Models\LandParcel;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\RealEstateDocument;
use Modules\RealEstate\Models\Sale;
use Modules\RealEstate\Models\ServiceRequest;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\Unit;
use Modules\RealEstate\Models\Valuation;
use Modules\RealEstate\Services\RealEstateUtilityBillingService;
use Modules\RealEstate\Services\RealEstateNumberService;
use Modules\RealEstate\Services\RealEstatePaymentService;
use Modules\RealEstate\Services\RealEstateService;
use Modules\RealEstate\Services\TenantLedgerService;
use Modules\RealEstate\Services\TenantBillingAlertService;
use Modules\RealEstate\Services\TenantOffboardingService;
use Modules\RealEstate\Support\RealEstateValidationRules;

class RealEstateOperationsController extends Controller
{
    public function storeProperty(Request $request, RealEstateService $service)
    {
        $service->createProperty($request->validate(RealEstateValidationRules::property()));
        return back()->with('status', 'Property saved.');
    }

    public function storeUnit(Request $request, RealEstateService $service)
    {
        $service->createUnit($request->validate(RealEstateValidationRules::unit()));
        return back()->with('status', 'Unit saved.');
    }

    public function storeTenant(Request $request, RealEstateService $service)
    {
        $data = $request->validate(RealEstateValidationRules::clientExtension('tenant') + [
            'id_number' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Prospect,Active,Notice Given,Moving Out,Moved Out,Archived,Blacklisted'],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'lease_start_date' => ['nullable', 'date'],
            'lease_end_date' => ['nullable', 'date', 'after_or_equal:lease_start_date'],
            'assignment_rent_amount' => ['nullable', 'numeric', 'min:0'],
            'assignment_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'assignment_billing_cycle' => ['nullable', 'in:Monthly,Quarterly,Annual'],
            'auto_billing' => ['nullable', 'boolean'],
        ]);
        $tenant = $service->createTenant($data);

        if (! empty($data['real_estate_unit_id'])) {
            $service->assignTenantToUnit($tenant, Unit::findOrFail($data['real_estate_unit_id']), $data);
        }

        return back()->with('status', ! empty($data['real_estate_unit_id']) ? 'Tenant saved and assigned to unit.' : 'Tenant profile saved through CRM.');
    }

    public function assignTenantUnit(Request $request, Tenant $tenant, RealEstateService $service)
    {
        $data = $request->validate([
            'real_estate_unit_id' => ['required', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'lease_start_date' => ['nullable', 'date'],
            'lease_end_date' => ['nullable', 'date', 'after_or_equal:lease_start_date'],
            'assignment_rent_amount' => ['nullable', 'numeric', 'min:0'],
            'assignment_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'assignment_billing_cycle' => ['nullable', 'in:Monthly,Quarterly,Annual'],
            'auto_billing' => ['nullable', 'boolean'],
        ]);

        $service->assignTenantToUnit($tenant, Unit::findOrFail($data['real_estate_unit_id']), $data);

        return redirect()->route('real-estate.dashboard', ['section' => 'tenants'])->with('status', 'Tenant assigned to unit and unit marked occupied.');
    }

    public function storeBuyer(Request $request, RealEstateService $service)
    {
        $data = $request->validate(RealEstateValidationRules::clientExtension('buyer') + [
            'budget' => ['nullable', 'numeric', 'min:0'],
            'preferred_locations' => ['nullable', 'string'],
            'property_interests' => ['nullable', 'string'],
            'status' => ['required', 'in:Prospect,Buyer,Investor,Inactive'],
        ]);
        $service->createBuyer($data);
        return back()->with('status', 'Buyer profile saved through CRM.');
    }

    public function storeAgent(Request $request, RealEstateService $service)
    {
        $service->createAgent($request->validate([
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'user_id' => ['nullable', 'exists:users,id'],
            'agent_number' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:Active,Suspended,Inactive'],
        ]));
        return back()->with('status', 'Agent saved.');
    }

    public function destroyAgent(Agent $agent)
    {
        if ($agent->listings()->exists() || $agent->sales()->exists() || $agent->commissions()->exists()) {
            throw ValidationException::withMessages(['agent' => 'Agent cannot be deleted because linked listings, sales, or commissions exist. Mark the agent inactive instead.']);
        }

        $agent->delete();

        return redirect()->route('real-estate.dashboard', ['section' => 'sales'])->with('status', 'Agent deleted.');
    }

    public function storeListing(Request $request, RealEstateService $service)
    {
        $service->createListing($request->validate([
            'real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_agent_id' => ['nullable', Rule::exists('real_estate_agents', 'id')->where('business_id', ActiveBusiness::id())],
            'listing_number' => ['nullable', 'string', 'max:50'],
            'listing_type' => ['required', 'in:Sale,Rent,Lease,Short Stay,Auction'],
            'price' => ['required', 'numeric', 'min:0'],
            'listing_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:listing_date'],
            'status' => ['required', 'in:Draft,Pending Approval,Approved,Published,Expired,Archived'],
            'is_featured' => ['nullable', 'boolean'],
            'public_ready' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string'],
        ]));
        return back()->with('status', 'Listing saved.');
    }

    public function approveListing(Listing $listing)
    {
        $listing->update(['status' => 'Approved', 'public_ready' => true]);
        return back()->with('status', 'Listing approved for portal/public publication.');
    }

    public function destroyListing(Listing $listing)
    {
        $listing->delete();

        return redirect()->route('real-estate.dashboard', ['section' => 'sales'])->with('status', 'Listing deleted.');
    }

    public function storeLease(Request $request, RealEstateService $service)
    {
        $service->createLease($request->validate([
            'real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_tenant_id' => ['required', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'document_template_id' => ['nullable', Rule::exists('document_templates', 'id')->where('business_id', ActiveBusiness::id())],
            'lease_number' => ['nullable', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:Monthly,Quarterly,Annual'],
            'grace_period_days' => ['nullable', 'integer', 'min:0'],
            'rent_escalation_percent' => ['nullable', 'numeric', 'min:0'],
            'next_bill_date' => ['nullable', 'date'],
            'status' => ['required', 'in:Draft,Active,Expired,Renewed,Terminated'],
            'auto_billing' => ['nullable', 'boolean'],
        ]));
        return back()->with('status', 'Lease saved.');
    }

    public function billLease(Request $request, Lease $lease, RealEstateService $service)
    {
        $charge = $service->generateRentInvoice($lease, $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'charge_type' => ['nullable', 'in:Monthly Rent,Quarterly Rent,Annual Rent,Service Charge,Utility Charge,Penalty,Deposit'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]));
        return back()->with('status', 'Rental invoice '.$charge->invoice?->invoice_number.' generated through shared Finance.');
    }

    public function recordClientPayment(Request $request, RealEstatePaymentService $service)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'required_without_all:tenant_id,unit_id,invoice_id', Rule::exists('clients', 'id')->where('business_id', ActiveBusiness::id())],
            'tenant_id' => ['nullable', 'required_without_all:client_id,unit_id,invoice_id', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'unit_id' => ['nullable', 'required_without_all:client_id,tenant_id,invoice_id', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'invoice_id' => ['nullable', 'required_without_all:client_id,tenant_id,unit_id', Rule::exists('invoices', 'id')->where('business_id', ActiveBusiness::id())],
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', ActiveBusiness::id())],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = match (true) {
            ! empty($data['invoice_id']) => Invoice::findOrFail($data['invoice_id']),
            ! empty($data['unit_id']) => $service->oldestOutstandingInvoiceForUnit((int) $data['unit_id']),
            ! empty($data['tenant_id']) => $service->oldestOutstandingInvoiceForTenant((int) $data['tenant_id']),
            default => $service->oldestOutstandingInvoiceForClient((int) $data['client_id']),
        };

        if (! $invoice) {
            throw ValidationException::withMessages(['invoice_id' => 'No outstanding Real Estate invoice matches the selected client, tenant, or unit.']);
        }

        if (! $service->isRealEstateInvoice($invoice)) {
            throw ValidationException::withMessages(['invoice_id' => 'Select a Real Estate invoice.']);
        }

        $context = $service->invoiceContext((int) $invoice->id);

        if (! empty($data['client_id']) && (int) $context['client_id'] !== (int) $data['client_id']) {
            throw ValidationException::withMessages(['invoice_id' => 'The selected invoice does not belong to the selected client.']);
        }

        if (! empty($data['tenant_id']) && (int) $context['tenant_id'] !== (int) $data['tenant_id']) {
            throw ValidationException::withMessages(['invoice_id' => 'The selected invoice does not belong to the selected tenant.']);
        }

        if (! empty($data['unit_id']) && (int) $context['unit_id'] !== (int) $data['unit_id']) {
            throw ValidationException::withMessages(['invoice_id' => 'The selected invoice does not belong to the selected unit.']);
        }

        $receipt = $service->recordClientPayment($invoice, $data);

        return redirect()
            ->route('real-estate.dashboard', ['section' => 'payments'])
            ->with('status', 'Client payment recorded. Receipt '.$receipt->receipt_number.' generated.');
    }

    public function updateTenantBillingAlerts(Request $request, Tenant $tenant, TenantBillingAlertService $alerts)
    {
        $alerts->updateSettings($tenant, $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'billing_alert_enabled' => ['nullable', 'boolean'],
            'billing_alert_frequency' => ['required', 'in:Monthly,Quarterly'],
            'billing_alert_day' => ['required', 'integer', 'min:1', 'max:28'],
            'billing_alert_subject' => ['nullable', 'string', 'max:255'],
        ]));

        return redirect()
            ->route('real-estate.dashboard', ['section' => 'tenant-alerts'])
            ->with('status', 'Tenant billing email alerts updated.');
    }

    public function storeSale(Request $request, RealEstateService $service)
    {
        $service->createSale($request->validate([
            'real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_buyer_id' => ['required', Rule::exists('real_estate_buyers', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_agent_id' => ['nullable', Rule::exists('real_estate_agents', 'id')->where('business_id', ActiveBusiness::id())],
            'sale_number' => ['nullable', 'string', 'max:50'],
            'sale_price' => ['required', 'numeric', 'min:0.01'],
            'deposit' => ['nullable', 'numeric', 'min:0'],
            'completion_date' => ['nullable', 'date'],
            'status' => ['required', 'in:Reserved,Installment,Agreement,Completed,Cancelled'],
        ]));
        return back()->with('status', 'Property sale saved.');
    }

    public function destroySale(Sale $sale)
    {
        if ($sale->invoice?->payments()->exists() || $sale->commissions()->exists()) {
            throw ValidationException::withMessages(['sale' => 'Sale cannot be deleted because invoice payments or commissions exist. Cancel the sale instead.']);
        }

        $sale->delete();

        return redirect()->route('real-estate.dashboard', ['section' => 'sales'])->with('status', 'Sale deleted.');
    }

    public function createCommission(Request $request, Sale $sale, RealEstateService $service)
    {
        $data = $request->validate([
            'real_estate_agent_id' => ['required', Rule::exists('real_estate_agents', 'id')->where('business_id', ActiveBusiness::id())],
            'commission_type' => ['required', 'in:Sales Commission,Rental Commission,Referral Commission'],
            'calculation_type' => ['required', 'in:Fixed,Percentage,Tier Based'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);

        $service->createCommission(Agent::findOrFail($data['real_estate_agent_id']), $data['commission_type'], $data['calculation_type'], (float) $data['rate'], (float) $sale->sale_price, $sale);
        return back()->with('status', 'Commission calculated.');
    }

    public function storeMaintenance(Request $request, RealEstateService $service)
    {
        $service->createMaintenance($request->validate([
            'real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_tenant_id' => ['nullable', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'technician_id' => ['nullable', 'exists:users,id'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('business_id', ActiveBusiness::id())],
            'maintenance_type' => ['required', 'in:Preventive,Corrective,Emergency'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'description' => ['required', 'string'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['nullable', 'date'],
            'status' => ['required', 'in:Open,Assigned,In Progress,Completed,Closed'],
        ]));
        return back()->with('status', 'Maintenance request saved.');
    }

    public function storeServiceRequest(Request $request, RealEstateService $service)
    {
        $service->createServiceRequest($request->validate([
            'real_estate_tenant_id' => ['nullable', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'request_type' => ['required', 'in:Plumbing Issues,Electrical Issues,Security Issues,Cleaning Requests,Repairs'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:Open,Assigned,In Progress,Resolved,Closed'],
        ]));

        return back()->with('status', 'Tenant service request saved.');
    }

    public function storeUtilityType(Request $request, RealEstateUtilityBillingService $service)
    {
        $service->createUtilityType($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'billing_method' => ['required', 'in:Metered,Flat,Subscription,Variable'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'is_custom' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]));

        return back()->with('status', 'Utility category saved.');
    }

    public function storeUtilityMeter(Request $request, RealEstateUtilityBillingService $service)
    {
        $service->createMeter($request->validate([
            'real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_tenant_id' => ['nullable', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_utility_type_id' => ['required', Rule::exists('real_estate_utility_types', 'id')->where('business_id', ActiveBusiness::id())],
            'meter_number' => ['required', 'string', 'max:100'],
            'meter_type' => ['required', 'in:Water Meter,Electricity Meter,Gas Meter,Custom Meter'],
            'previous_reading' => ['nullable', 'numeric', 'min:0'],
            'current_reading' => ['nullable', 'numeric', 'min:0'],
            'reading_date' => ['nullable', 'date'],
            'rate_per_unit' => ['nullable', 'numeric', 'min:0'],
            'smart_meter_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:Active,Inactive,Disconnected,Faulty'],
        ]));

        return back()->with('status', 'Utility meter saved.');
    }

    public function storeUtilityReading(Request $request, RealEstateUtilityBillingService $service)
    {
        $service->recordReading($request->validate([
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
        ]), (bool) $request->boolean('generate_bill', true));

        return back()->with('status', 'Meter reading saved and billing calculated.');
    }

    public function storeUtilityBill(Request $request, RealEstateUtilityBillingService $service)
    {
        $service->createUtilityBill($request->validate([
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

        return back()->with('status', 'Utility bill generated through shared Finance.');
    }

    public function storeAmenity(Request $request, RealEstateUtilityBillingService $service)
    {
        $service->createAmenity($request->validate([
            'real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'booking_rules' => ['nullable', 'string'],
            'fee_type' => ['required', 'in:Free,Fixed,Hourly,Daily'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]));

        return back()->with('status', 'Amenity saved.');
    }

    public function storeAmenityBooking(Request $request, RealEstateUtilityBillingService $service)
    {
        $service->createAmenityBooking($request->validate([
            'real_estate_amenity_id' => ['required', Rule::exists('real_estate_amenities', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_tenant_id' => ['required', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())],
            'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())],
            'booking_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'charge_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Pending,Confirmed,Completed,Cancelled'],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Amenity booking saved.');
    }

    public function generateTenantStatement(Request $request, Tenant $tenant, TenantLedgerService $ledger)
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $statement = $ledger->statement($tenant, $data['period_start'], $data['period_end']);

        return back()->with('status', 'Tenant statement '.$statement->statement_number.' generated.');
    }

    public function startTenantNotice(Request $request, Tenant $tenant, TenantOffboardingService $offboarding)
    {
        $offboarding->startNotice($tenant, $request->validate([
            'notice_date' => ['nullable', 'date'],
            'move_out_date' => ['nullable', 'date', 'after_or_equal:notice_date'],
            'offboarding_notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Tenant exit notice recorded.');
    }

    public function progressTenantExit(Request $request, Tenant $tenant, TenantOffboardingService $offboarding)
    {
        $data = $request->validate([
            'step' => ['required', Rule::in(TenantOffboardingService::STEPS)],
            'termination_date' => ['nullable', 'date'],
            'final_inspection_date' => ['nullable', 'date'],
            'move_out_date' => ['nullable', 'date'],
            'offboarding_notes' => ['nullable', 'string'],
        ]);

        $offboarding->progress($tenant, $data['step'], $data);

        return back()->with('status', 'Tenant exit workflow updated.');
    }

    public function archiveTenant(Request $request, Tenant $tenant, TenantOffboardingService $offboarding)
    {
        $offboarding->archive($tenant, $request->validate([
            'move_out_date' => ['nullable', 'date'],
            'offboarding_notes' => ['nullable', 'string'],
        ]));

        return back()->with('status', 'Tenant archived and unit released.');
    }

    public function restoreTenant(Tenant $tenant, TenantOffboardingService $offboarding)
    {
        $offboarding->restore($tenant);

        return back()->with('status', 'Tenant restored as prospect.');
    }

    public function destroyTenant(Request $request, Tenant $tenant, TenantOffboardingService $offboarding)
    {
        abort_unless($request->user()?->role === 'super_admin', 403, 'Super Admin permission required.');

        $request->validate(['confirm_delete' => ['accepted']]);
        $offboarding->deleteIfAllowed($tenant);

        return back()->with('status', 'Tenant permanently deleted.');
    }

    public function storeSimpleRecord(Request $request, string $type, RealEstateService $service)
    {
        match ($type) {
            'inspection' => $service->createInspection($this->simpleRules($request, $type)),
            'service-request' => $service->createServiceRequest($this->simpleRules($request, $type)),
            'valuation' => $service->createValuation($this->simpleRules($request, $type)),
            'land' => $service->createLandParcel($this->simpleRules($request, $type)),
            'development' => $service->createDevelopmentProject($this->simpleRules($request, $type)),
            default => abort(404),
        };

        return back()->with('status', str($type)->headline().' saved.');
    }

    public function storeDocument(Request $request)
    {
        $data = $request->validate([
            'documentable_type' => ['nullable', Rule::in(array_keys($this->documentableTypes()))],
            'documentable_id' => ['nullable', 'integer', 'min:1'],
            'document_template_id' => ['nullable', Rule::exists('document_templates', 'id')->where('business_id', ActiveBusiness::id())],
            'document_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Draft,Active,Expired,Archived'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        [$documentableType, $documentableId] = $this->resolveDocumentable($data['documentable_type'] ?? null, $data['documentable_id'] ?? null);

        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('real-estate/documents', 'public')
            : null;

        RealEstateDocument::create([
            'documentable_type' => $documentableType,
            'documentable_id' => $documentableId,
            'document_template_id' => $data['document_template_id'] ?? null,
            'document_type' => $data['document_type'],
            'title' => $data['title'],
            'file_path' => $filePath,
            'status' => $data['status'],
        ]);

        return redirect()->route('real-estate.dashboard', ['section' => 'documents'])->with('status', 'Real Estate document saved.');
    }

    public function downloadDocument(RealEstateDocument $document)
    {
        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path);
    }

    public function destroyDocument(RealEstateDocument $document)
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('status', 'Real Estate document archived.');
    }

    private function simpleRules(Request $request, string $type): array
    {
        return match ($type) {
            'inspection' => $request->validate(['real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())], 'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())], 'inspector_id' => ['nullable', 'exists:users,id'], 'inspection_type' => ['required', 'in:Move In Inspection,Move Out Inspection,Routine Inspection,Maintenance Inspection,Valuation Inspection'], 'inspection_date' => ['required', 'date'], 'findings' => ['nullable', 'string'], 'recommendations' => ['nullable', 'string'], 'photos' => ['nullable', 'string'], 'status' => ['required', 'in:Draft,Completed,Approved']]),
            'service-request' => $request->validate(['real_estate_tenant_id' => ['nullable', Rule::exists('real_estate_tenants', 'id')->where('business_id', ActiveBusiness::id())], 'real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())], 'real_estate_unit_id' => ['nullable', Rule::exists('real_estate_units', 'id')->where('business_id', ActiveBusiness::id())], 'assigned_to' => ['nullable', 'exists:users,id'], 'request_type' => ['required', 'in:Plumbing Issues,Electrical Issues,Security Issues,Cleaning Requests,Repairs'], 'description' => ['required', 'string'], 'status' => ['required', 'in:Open,Assigned,In Progress,Resolved,Closed']]),
            'valuation' => $request->validate(['real_estate_property_id' => ['required', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())], 'valuer_id' => ['nullable', 'exists:users,id'], 'valuation_date' => ['required', 'date'], 'market_value' => ['required', 'numeric', 'min:0'], 'rental_value' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string'], 'status' => ['required', 'in:Draft,Approved,Archived']]),
            'land' => $request->validate(['real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())], 'parcel_number' => ['required', 'string', 'max:100'], 'title_number' => ['nullable', 'string', 'max:100'], 'land_size' => ['required', 'numeric', 'min:0'], 'land_size_unit' => ['required', 'in:Acres,Hectares,Sq Ft,Sq M'], 'zoning' => ['nullable', 'string', 'max:100'], 'ownership_status' => ['required', 'in:Owned,Leased,Under Transfer,Disputed'], 'ownership_history' => ['nullable', 'string'], 'sales_history' => ['nullable', 'string']]),
            'development' => $request->validate(['project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', ActiveBusiness::id())], 'real_estate_property_id' => ['nullable', Rule::exists('real_estate_properties', 'id')->where('business_id', ActiveBusiness::id())], 'development_number' => ['nullable', 'string', 'max:50'], 'name' => ['required', 'string', 'max:255'], 'phase' => ['nullable', 'string', 'max:100'], 'budget' => ['nullable', 'numeric', 'min:0'], 'actual_cost' => ['nullable', 'numeric', 'min:0'], 'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'], 'contractor' => ['nullable', 'string', 'max:255'], 'status' => ['required', 'in:Planning,Approval,Construction,Completed']]),
        };
    }

    private function resolveDocumentable(?string $type, $id): array
    {
        if (! $type || ! $id) {
            return [null, null];
        }

        $class = $this->documentableTypes()[$type] ?? abort(422, 'Unsupported Real Estate document target.');
        $model = $class::whereKey($id)->firstOrFail();

        return [$model->getMorphClass(), $model->getKey()];
    }

    private function documentableTypes(): array
    {
        return [
            'property' => Property::class,
            'unit' => Unit::class,
            'tenant' => Tenant::class,
            'lease' => Lease::class,
            'listing' => Listing::class,
            'sale' => Sale::class,
            'inspection' => Inspection::class,
            'service-request' => ServiceRequest::class,
            'valuation' => Valuation::class,
            'land' => LandParcel::class,
            'development' => DevelopmentProject::class,
        ];
    }
}
