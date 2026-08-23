<?php

namespace Modules\RealEstate\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Receipt;
use App\Models\Supplier;
use App\Models\User;
use Modules\RealEstate\Models\Agent;
use Modules\RealEstate\Models\Amenity;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\Buyer;
use Modules\RealEstate\Models\Commission;
use Modules\RealEstate\Models\DevelopmentProject;
use Modules\RealEstate\Models\Inspection;
use Modules\RealEstate\Models\LandParcel;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\MaintenanceRequest;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\RealEstateDocument;
use Modules\RealEstate\Models\RentalCharge;
use Modules\RealEstate\Models\Sale;
use Modules\RealEstate\Models\ServiceRequest;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\TenantLedger;
use Modules\RealEstate\Models\TenantStatement;
use Modules\RealEstate\Models\UtilityBill;
use Modules\RealEstate\Models\UtilityConsumption;
use Modules\RealEstate\Models\UtilityMeter;
use Modules\RealEstate\Models\UtilityType;
use Modules\RealEstate\Models\Unit;
use Modules\RealEstate\Models\Valuation;
use Modules\RealEstate\Repositories\RealEstateRepository;

class RealEstateDashboardController extends Controller
{
    public function __invoke(RealEstateRepository $realEstate)
    {
        $realEstateInvoiceIds = collect()
            ->merge(RentalCharge::whereNotNull('invoice_id')->pluck('invoice_id'))
            ->merge(UtilityBill::whereNotNull('invoice_id')->pluck('invoice_id'))
            ->merge(AmenityBooking::whereNotNull('invoice_id')->pluck('invoice_id'))
            ->filter()
            ->unique()
            ->values();
        $paymentInvoices = $realEstateInvoiceIds->isEmpty()
            ? collect()
            : Invoice::with('client')->whereIn('id', $realEstateInvoiceIds)->where('balance', '>', 0)->latest('invoice_date')->limit(100)->get();
        $paymentInvoiceIds = $paymentInvoices->pluck('id');
        $paymentInvoiceContexts = collect();
        if ($paymentInvoiceIds->isNotEmpty()) {
            RentalCharge::with('lease')->whereIn('invoice_id', $paymentInvoiceIds)->get()->each(fn ($charge) => $paymentInvoiceContexts->put($charge->invoice_id, [
                'tenant_id' => $charge->lease?->real_estate_tenant_id,
                'unit_id' => $charge->lease?->real_estate_unit_id,
            ]));
            UtilityBill::whereIn('invoice_id', $paymentInvoiceIds)->get()->each(fn ($bill) => $paymentInvoiceContexts->put($bill->invoice_id, [
                'tenant_id' => $bill->real_estate_tenant_id,
                'unit_id' => $bill->real_estate_unit_id,
            ]));
            AmenityBooking::whereIn('invoice_id', $paymentInvoiceIds)->get()->each(fn ($booking) => $paymentInvoiceContexts->put($booking->invoice_id, [
                'tenant_id' => $booking->real_estate_tenant_id,
                'unit_id' => $booking->real_estate_unit_id,
            ]));
        }
        $paymentTenantIds = $paymentInvoiceContexts->pluck('tenant_id')->filter()->unique()->values();
        $paymentUnitIds = $paymentInvoiceContexts->pluck('unit_id')->filter()->unique()->values();
        $realEstateInvoices = $realEstateInvoiceIds->isEmpty()
            ? collect()
            : Invoice::with('client', 'receipts')
                ->whereIn('id', $realEstateInvoiceIds)
                ->latest('invoice_date')
                ->limit(100)
                ->get();
        $realEstateReceipts = $realEstateInvoiceIds->isEmpty()
            ? collect()
            : Receipt::with('invoice.client', 'payment.paymentMethod')
                ->whereIn('invoice_id', $realEstateInvoiceIds)
                ->latest('payment_date')
                ->limit(100)
                ->get();

        return view('real-estate.dashboard', [
            'metrics' => $realEstate->metrics(),
            'portfolioRows' => $realEstate->portfolioRows(),
            'properties' => Property::with('units')->orderBy('property_name')->get(),
            'units' => Unit::with('property')->orderBy('unit_number')->limit(100)->get(),
            'availableUnits' => Unit::with('property')->where('occupancy_status', 'Vacant')->orderBy('unit_number')->limit(100)->get(),
            'tenants' => Tenant::with('client', 'leases.property', 'leases.unit')->current()->latest()->limit(50)->get(),
            'archiveTenants' => Tenant::with('client', 'leases.property', 'leases.unit')->archivedLifecycle()->latest()->limit(75)->get(),
            'buyers' => Buyer::with('client')->latest()->limit(50)->get(),
            'agents' => Agent::with('branch')->latest()->limit(50)->get(),
            'leases' => Lease::with('tenant.client', 'property', 'unit')->latest()->limit(50)->get(),
            'listings' => Listing::with('property', 'unit', 'agent')->latest()->limit(50)->get(),
            'charges' => RentalCharge::with('lease.tenant.client', 'invoice')->latest()->limit(50)->get(),
            'paymentInvoices' => $paymentInvoices,
            'paymentInvoiceContexts' => $paymentInvoiceContexts,
            'paymentClients' => $paymentInvoices->pluck('client')->filter()->unique('id')->sortBy('name')->values(),
            'paymentTenants' => $paymentTenantIds->isEmpty() ? collect() : Tenant::with('client')->whereIn('id', $paymentTenantIds)->orderBy('tenant_number')->get(),
            'paymentUnits' => $paymentUnitIds->isEmpty() ? collect() : Unit::with('property')->whereIn('id', $paymentUnitIds)->orderBy('unit_number')->get(),
            'realEstateInvoices' => $realEstateInvoices,
            'realEstateReceipts' => $realEstateReceipts,
            'realEstatePayments' => $realEstateInvoiceIds->isEmpty()
                ? collect()
                : Payment::with('invoice.client', 'paymentMethod', 'receipt')->whereIn('invoice_id', $realEstateInvoiceIds)->latest('payment_date')->limit(50)->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'utilityTypes' => UtilityType::latest()->limit(100)->get(),
            'utilityMeters' => UtilityMeter::with('property', 'unit', 'tenant.client', 'utilityType')->latest()->limit(50)->get(),
            'utilityBills' => UtilityBill::with('tenant.client', 'property', 'unit', 'utilityType', 'invoice', 'meterReading')->latest()->limit(50)->get(),
            'utilityConsumption' => UtilityConsumption::with('tenant.client', 'unit', 'utilityType')->latest()->limit(50)->get(),
            'amenities' => Amenity::with('property')->latest()->limit(50)->get(),
            'amenityBookings' => AmenityBooking::with('amenity', 'tenant.client', 'unit', 'invoice')->latest()->limit(50)->get(),
            'tenantLedgerRows' => TenantLedger::with('tenant.client', 'property', 'unit', 'invoice')->latest()->limit(75)->get(),
            'tenantStatements' => TenantStatement::with('tenant.client', 'lease.property', 'lease.unit')->latest()->limit(50)->get(),
            'sales' => Sale::with('buyer.client', 'property', 'unit', 'agent')->latest()->limit(50)->get(),
            'commissions' => Commission::with('agent')->latest()->limit(50)->get(),
            'maintenance' => MaintenanceRequest::with('property', 'unit', 'tenant.client')->latest()->limit(50)->get(),
            'serviceRequests' => ServiceRequest::with('tenant.client', 'property', 'unit', 'assignee')->latest()->limit(50)->get(),
            'inspections' => Inspection::with('property', 'unit', 'inspector')->latest()->limit(30)->get(),
            'valuations' => Valuation::with('property', 'valuer')->latest()->limit(30)->get(),
            'landParcels' => LandParcel::with('property')->latest()->limit(30)->get(),
            'developmentProjects' => DevelopmentProject::with('property', 'sharedProject')->latest()->limit(30)->get(),
            'documents' => RealEstateDocument::with('documentable', 'documentTemplate')->latest()->limit(75)->get(),
            'branches' => Branch::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->limit(100)->get(),
            'users' => $this->activeBusinessUsers()->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'documentTemplates' => DocumentTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
