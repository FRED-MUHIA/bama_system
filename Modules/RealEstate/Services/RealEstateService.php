<?php

namespace Modules\RealEstate\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RealEstate\Models\Agent;
use Modules\RealEstate\Models\Buyer;
use Modules\RealEstate\Models\Commission;
use Modules\RealEstate\Models\DevelopmentProject;
use Modules\RealEstate\Models\Inspection;
use Modules\RealEstate\Models\LandParcel;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\MaintenanceRequest;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\RentalCharge;
use Modules\RealEstate\Models\Sale;
use Modules\RealEstate\Models\ServiceRequest;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\Unit;
use Modules\RealEstate\Models\Valuation;

class RealEstateService
{
    public function __construct(
        private RealEstateNumberService $numbers,
        private DocumentService $documents,
        private TenantLedgerService $ledger,
        private RealEstateInvoiceProfileService $invoiceProfiles,
    ) {}

    public function createProperty(array $data): Property
    {
        $data['property_code'] = $data['property_code'] ?? $this->numbers->next('real_estate_properties', 'property_code', 'PROP');
        $data = $this->zeroEmptyDefaults($data, ['acquisition_cost', 'market_value']);

        return Property::create($data);
    }

    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $clientId = $data['client_id'] ?? null;
            if (! $clientId) {
                $client = Client::create(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'type' => 'individual']);
                $clientId = $client->id;
            }

            return Tenant::create([
                'client_id' => $clientId,
                'tenant_number' => $data['tenant_number'] ?? $this->numbers->next('real_estate_tenants', 'tenant_number', 'TEN'),
                'id_number' => $data['id_number'] ?? null,
                'passport_number' => $data['passport_number'] ?? null,
                'employer' => $data['employer'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'status' => $data['status'] ?? 'Prospect',
            ]);
        });
    }

    public function createBuyer(array $data): Buyer
    {
        return DB::transaction(function () use ($data) {
            $clientId = $data['client_id'] ?? null;
            if (! $clientId) {
                $client = Client::create(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'type' => 'individual']);
                $clientId = $client->id;
            }

            return Buyer::create([
                'client_id' => $clientId,
                'buyer_number' => $data['buyer_number'] ?? $this->numbers->next('real_estate_buyers', 'buyer_number', 'BUY'),
                'budget' => $this->zeroIfEmpty($data['budget'] ?? null),
                'preferred_locations' => $data['preferred_locations'] ?? null,
                'property_interests' => $data['property_interests'] ?? null,
                'status' => $data['status'] ?? 'Prospect',
            ]);
        });
    }

    public function createListing(array $data): Listing
    {
        $data['listing_number'] = $data['listing_number'] ?? $this->numbers->next('real_estate_listings', 'listing_number', 'LST');
        $data['features'] = $this->csvToArray($data['features'] ?? null);
        $data = $this->zeroEmptyDefaults($data, ['price']);

        return Listing::create($data);
    }

    public function createLease(array $data): Lease
    {
        return DB::transaction(function () use ($data) {
            $data['lease_number'] = $data['lease_number'] ?? $this->numbers->next('real_estate_leases', 'lease_number', 'LSE');
            $data = $this->zeroEmptyDefaults($data, ['deposit_amount', 'grace_period_days', 'rent_escalation_percent']);
            $lease = Lease::create($data);
            $lease->unit?->update(['occupancy_status' => 'Occupied']);
            $lease->property->update(['status' => 'Occupied']);
            return $lease;
        });
    }

    public function assignTenantToUnit(Tenant $tenant, Unit $unit, array $data = []): Lease
    {
        return DB::transaction(function () use ($tenant, $unit, $data) {
            $unit = Unit::whereKey($unit->id)->lockForUpdate()->firstOrFail();

            if ($unit->occupancy_status !== 'Vacant') {
                throw ValidationException::withMessages(['real_estate_unit_id' => 'Only vacant units can be assigned to a tenant.']);
            }

            $lease = $this->createLease([
                'real_estate_property_id' => $unit->real_estate_property_id,
                'real_estate_unit_id' => $unit->id,
                'real_estate_tenant_id' => $tenant->id,
                'start_date' => $data['lease_start_date'] ?? now()->toDateString(),
                'end_date' => $data['lease_end_date'] ?? null,
                'rent_amount' => $this->zeroIfEmpty($data['assignment_rent_amount'] ?? $unit->rent_amount),
                'deposit_amount' => $this->zeroIfEmpty($data['assignment_deposit_amount'] ?? null),
                'billing_cycle' => $data['assignment_billing_cycle'] ?? 'Monthly',
                'status' => 'Active',
                'auto_billing' => (bool) ($data['auto_billing'] ?? false),
            ]);

            if ($tenant->status === 'Prospect') {
                $tenant->update(['status' => 'Active']);
            }

            return $lease;
        });
    }

    public function generateRentInvoice(Lease $lease, array $data = []): RentalCharge
    {
        return DB::transaction(function () use ($lease, $data) {
            $lease->load('tenant.client', 'property', 'unit');
            $amount = round((float) ($data['amount'] ?? $lease->rent_amount), 2);
            $periodStart = $data['period_start'] ?? now()->startOfMonth()->toDateString();
            $periodEnd = $data['period_end'] ?? now()->endOfMonth()->toDateString();
            $dueDate = $data['due_date'] ?? now()->addDays($lease->grace_period_days)->toDateString();

            $invoice = Invoice::create([
                'client_id' => $lease->tenant->client_id,
                'invoice_number' => $this->documents->number('invoice'),
                'public_token' => \Illuminate\Support\Str::random(48),
                'invoice_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'payment_status' => 'unpaid',
                'subtotal' => $amount,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => $amount,
                'amount_paid' => 0,
                'balance' => $amount,
                'notes' => 'Rental billing for lease '.$lease->lease_number,
            ]);

            $invoice->items()->create([
                'description' => trim('Rent - '.$lease->property->property_name.' '.($lease->unit?->unit_number ? 'Unit '.$lease->unit->unit_number : '')),
                'quantity' => 1,
                'unit_price' => $amount,
                'discount' => 0,
                'tax_rate' => 0,
                'line_total' => $amount,
            ]);

            $charge = RentalCharge::create([
                'real_estate_lease_id' => $lease->id,
                'invoice_id' => $invoice->id,
                'charge_number' => $this->numbers->next('real_estate_rental_charges', 'charge_number', 'RNT'),
                'charge_type' => $data['charge_type'] ?? 'Monthly Rent',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => $dueDate,
                'amount' => $amount,
                'penalty_amount' => 0,
                'status' => 'Outstanding',
            ]);

            $this->invoiceProfiles->apply($invoice, $lease->tenant, $lease, $charge, 'Rent');

            $this->ledger->recordCharge($lease->tenant, $charge, 'Rent Charge', $charge->charge_type.' '.$charge->charge_number, $amount, [
                'real_estate_lease_id' => $lease->id,
                'real_estate_property_id' => $lease->real_estate_property_id,
                'real_estate_unit_id' => $lease->real_estate_unit_id,
                'invoice_id' => $invoice->id,
                'entry_date' => $periodEnd,
            ]);

            return $charge;
        });
    }

    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $data['sale_number'] = $data['sale_number'] ?? $this->numbers->next('real_estate_sales', 'sale_number', 'SAL');
            $data = $this->zeroEmptyDefaults($data, ['deposit']);
            $data['balance'] = max((float) $data['sale_price'] - (float) ($data['deposit'] ?? 0), 0);
            $sale = Sale::create($data);
            $sale->unit?->update(['occupancy_status' => 'Reserved']);
            $sale->property->update(['status' => 'Reserved']);
            return $sale;
        });
    }

    public function createCommission(Agent $agent, string $type, string $calculation, float $rate, float $base, $source = null): Commission
    {
        $earned = $calculation === 'Fixed' ? $rate : round($base * ($rate / 100), 2);
        return Commission::create([
            'real_estate_agent_id' => $agent->id,
            'commissionable_type' => $source?->getMorphClass(),
            'commissionable_id' => $source?->getKey(),
            'commission_number' => $this->numbers->next('real_estate_commissions', 'commission_number', 'COM'),
            'commission_type' => $type,
            'calculation_type' => $calculation,
            'rate' => $rate,
            'base_amount' => $base,
            'earned_amount' => $earned,
            'paid_amount' => 0,
            'earned_date' => now()->toDateString(),
            'status' => 'Earned',
        ]);
    }

    public function createAgent(array $data): Agent
    {
        $data['agent_number'] = $data['agent_number'] ?? $this->numbers->next('real_estate_agents', 'agent_number', 'AGT');
        return Agent::create($data);
    }

    public function createUnit(array $data): Unit
    {
        $data = $this->zeroEmptyDefaults($data, ['bedrooms', 'bathrooms', 'square_footage', 'rent_amount', 'sale_price']);

        return Unit::create($data);
    }

    public function createMaintenance(array $data): MaintenanceRequest
    {
        $data['request_number'] = $data['request_number'] ?? $this->numbers->next('real_estate_maintenance_requests', 'request_number', 'MNT');
        $data = $this->zeroEmptyDefaults($data, ['estimated_cost', 'actual_cost']);

        return MaintenanceRequest::create($data);
    }

    public function createServiceRequest(array $data): ServiceRequest
    {
        $data['request_number'] = $data['request_number'] ?? $this->numbers->next('real_estate_service_requests', 'request_number', 'SRV');

        if (in_array($data['status'] ?? null, ['Resolved', 'Closed'], true)) {
            $data['resolved_at'] ??= now();
            $data['resolution_minutes'] ??= 0;
        }

        return ServiceRequest::create($data);
    }

    public function createInspection(array $data): Inspection
    {
        $data['inspection_number'] = $data['inspection_number'] ?? $this->numbers->next('real_estate_inspections', 'inspection_number', 'INS');
        $data['photos'] = $this->csvToArray($data['photos'] ?? null);

        return Inspection::create($data);
    }

    public function createValuation(array $data): Valuation
    {
        $data = $this->zeroEmptyDefaults($data, ['rental_value']);
        $valuation = Valuation::create($data);

        if (($data['status'] ?? null) === 'Approved') {
            $valuation->property->update(['market_value' => $data['market_value']]);
        }

        return $valuation;
    }

    public function createLandParcel(array $data): LandParcel
    {
        $data['ownership_history'] = $this->csvToArray($data['ownership_history'] ?? null);
        $data['sales_history'] = $this->csvToArray($data['sales_history'] ?? null);
        $data = $this->zeroEmptyDefaults($data, ['land_size']);

        return LandParcel::create($data);
    }

    public function createDevelopmentProject(array $data): DevelopmentProject
    {
        $data['development_number'] = $data['development_number'] ?? $this->numbers->next('real_estate_development_projects', 'development_number', 'DEV');
        $data = $this->zeroEmptyDefaults($data, ['budget', 'actual_cost', 'progress_percent']);

        return DevelopmentProject::create($data);
    }

    private function zeroEmptyDefaults(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $data[$field] = $this->zeroIfEmpty($data[$field] ?? null);
        }

        return $data;
    }

    private function zeroIfEmpty($value)
    {
        return $value === null || $value === '' ? 0 : $value;
    }

    private function csvToArray($value): ?array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (! $value) {
            return null;
        }

        return collect(explode(',', $value))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
}
