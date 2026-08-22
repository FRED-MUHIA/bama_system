<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\AdminAuditLog;
use App\Models\DocumentTemplate;
use App\Models\IamPermission;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\RealEstate\Models\Agent;
use Modules\RealEstate\Models\AmenityBooking;
use Modules\RealEstate\Models\Buyer;
use Modules\RealEstate\Models\DevelopmentProject;
use Modules\RealEstate\Models\Inspection;
use Modules\RealEstate\Models\LandParcel;
use Modules\RealEstate\Models\Lease;
use Modules\RealEstate\Models\Listing;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Models\RealEstateDocument;
use Modules\RealEstate\Models\RentalCharge;
use Modules\RealEstate\Models\Sale;
use Modules\RealEstate\Models\ServiceRequest;
use Modules\RealEstate\Models\Tenant;
use Modules\RealEstate\Models\TenantLedger;
use Modules\RealEstate\Models\TenantStatement;
use Modules\RealEstate\Models\UtilityBill;
use Modules\RealEstate\Models\UtilityMeter;
use Modules\RealEstate\Models\UtilityType;
use Modules\RealEstate\Models\Unit;
use Modules\RealEstate\Models\Valuation;
use Tests\TestCase;

class RealEstateIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformTenant $tenant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Acme Estates',
            'slug' => 'acme-estates',
            'industry' => 'RealEstate',
            'sub_industry' => 'enterprise',
            'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);

        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme Estates',
            'slug' => 'acme-estates',
            'industry' => 'RealEstate',
            'is_active' => true,
        ]);
        ActiveBusiness::switchTo($this->business);
        app(ModuleRegistry::class)->enableDefaultsFor($this->tenant);

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($this->admin)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);
        app(IamService::class)->bootstrap();
    }

    protected function tearDown(): void
    {
        foreach ([ActiveTenant::class => ['current', 'fallback', 'id', 'idResolved'], ActiveBusiness::class => ['current', 'default']] as $class => $properties) {
            $reflection = new \ReflectionClass($class);
            foreach ($properties as $property) {
                $ref = $reflection->getProperty($property);
                $ref->setAccessible(true);
                $ref->setValue(null, $property === 'idResolved' ? false : null);
            }
        }

        parent::tearDown();
    }

    public function test_real_estate_workflow_creates_lease_and_shared_finance_invoice(): void
    {
        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Tulsi Towers',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
            'city' => 'Nairobi',
            'acquisition_cost' => '',
            'market_value' => 45000000,
        ])->assertRedirect();

        $property = Property::firstOrFail();
        $this->assertSame(0.0, (float) $property->acquisition_cost);

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'A-101',
            'unit_type' => 'Two Bedroom',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'occupancy_status' => 'Vacant',
            'rent_amount' => 85000,
            'sale_price' => 0,
        ])->assertRedirect();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Jane Tenant',
            'phone' => '+254700000001',
            'email' => 'jane@example.test',
            'id_number' => 'ID-7788',
            'status' => 'Active',
        ])->assertRedirect();

        $unit = Unit::firstOrFail();
        $tenant = Tenant::with('client')->firstOrFail();

        $this->post(route('real-estate.leases.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'rent_amount' => 85000,
            'deposit_amount' => 85000,
            'billing_cycle' => 'Monthly',
            'grace_period_days' => 5,
            'status' => 'Active',
        ])->assertRedirect();

        $lease = Lease::firstOrFail();

        $this->post(route('real-estate.leases.bill', $lease), [
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'due_date' => '2026-08-05',
        ])->assertRedirect();

        $charge = RentalCharge::with('invoice')->firstOrFail();

        $this->assertSame('Outstanding', $charge->status);
        $this->assertSame(85000.0, (float) $charge->amount);
        $this->assertInstanceOf(Invoice::class, $charge->invoice);
        $this->assertSame($tenant->client_id, $charge->invoice->client_id);
        $this->assertSame('real_estate', $charge->invoice->industry_module);
        $this->assertMatchesRegularExpression('/^REI-2026-\d{4}$/', $charge->invoice->industry_reference);
        $this->assertSame('Acme Estates', $charge->invoice->issuer_profile['name']);
        $this->assertSame('Real Estate Management', $charge->invoice->issuer_profile['subtitle']);
        $this->assertSame($tenant->tenant_number, $charge->invoice->recipient_profile['tenant_number']);
        $this->assertSame('ID-7788', $charge->invoice->recipient_profile['id_number']);
        $this->assertSame('Tulsi Towers', $charge->invoice->industry_context['property_name']);
        $this->assertSame('A-101', $charge->invoice->industry_context['unit_number']);
        $this->assertSame($charge->charge_number, $charge->invoice->industry_context['source_reference']);
        $this->assertDatabaseHas('journal_entries', ['source_type' => Invoice::class, 'source_id' => $charge->invoice_id, 'status' => 'Posted']);
        $this->assertEquals(85000, JournalEntry::where('source_id', $charge->invoice_id)->firstOrFail()->total_debit);

        $this->get(route('public.invoices.show', $charge->invoice->public_token))
            ->assertOk()
            ->assertSee($charge->invoice->industry_reference)
            ->assertSee($tenant->tenant_number)
            ->assertSee('Tulsi Towers')
            ->assertSee('A-101');

        $this->post(route('real-estate.payments.store'), [
            'invoice_id' => $charge->invoice_id,
            'amount' => 25000,
            'payment_date' => '2026-08-02',
            'reference' => 'MPESA-RENT-1',
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'payments']));

        $charge->refresh();
        $invoice = $charge->invoice->fresh();
        $this->assertSame('Partial', $charge->status);
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame(25000.0, (float) $invoice->amount_paid);
        $this->assertSame(60000.0, (float) $invoice->balance);
        $this->assertInstanceOf(Payment::class, Payment::first());
        $this->assertInstanceOf(Receipt::class, Receipt::first());
        $this->assertDatabaseHas('journal_entries', ['source_type' => Payment::class, 'source_id' => Payment::firstOrFail()->id, 'status' => 'Posted']);
        $this->assertDatabaseHas('real_estate_tenant_ledgers', ['real_estate_tenant_id' => $tenant->id, 'entry_type' => 'Payment', 'credit' => 25000]);

        $this->post(route('real-estate.payments.store'), [
            'client_id' => $tenant->client_id,
            'amount' => 10000,
            'payment_date' => '2026-08-03',
            'reference' => 'CASH-RENT-2',
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'payments']));

        $this->assertSame(35000.0, (float) $invoice->fresh()->amount_paid);
        $this->assertDatabaseHas('real_estate_tenant_ledgers', ['real_estate_tenant_id' => $tenant->id, 'entry_type' => 'Payment', 'credit' => 10000]);

        $this->post(route('real-estate.payments.store'), [
            'tenant_id' => $tenant->id,
            'amount' => 5000,
            'payment_date' => '2026-08-04',
            'reference' => 'TENANT-RENT-3',
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'payments']));

        $this->post(route('real-estate.payments.store'), [
            'unit_id' => $unit->id,
            'amount' => 5000,
            'payment_date' => '2026-08-05',
            'reference' => 'UNIT-RENT-4',
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'payments']));

        $this->assertSame(45000.0, (float) $invoice->fresh()->amount_paid);
        $this->assertDatabaseHas('real_estate_tenant_ledgers', ['real_estate_tenant_id' => $tenant->id, 'entry_type' => 'Payment', 'credit' => 5000]);

        $receipt = Receipt::firstOrFail();
        $this->get(route('real-estate.dashboard', ['section' => 'payments']))
            ->assertOk()
            ->assertSee('Real Estate Invoices')
            ->assertSee($invoice->invoice_number)
            ->assertSee('Real Estate Receipts')
            ->assertSee($receipt->receipt_number);
    }

    public function test_dashboard_api_reports_and_navigation_are_available(): void
    {
        Property::create(['property_code' => 'PROP-1', 'property_name' => 'Sarin Offices', 'property_type' => 'Office', 'ownership_type' => 'Owned', 'status' => 'Available', 'market_value' => 10000000]);

        $this->get(route('real-estate.dashboard'))->assertOk()->assertSee('Real Estate Management')->assertSee('Portfolio Dashboard');
        $this->getJson(route('api.v1.real-estate.dashboard'))->assertOk()->assertJsonPath('data.properties', 1);
        $this->get(route('real-estate.reports.csv', 'properties'))->assertOk()->assertDownload('real-estate-properties.csv');

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Properties', $labels);
        $this->assertContains('Rental Billing', $labels);
        $this->assertContains('Documents', $labels);
        $this->assertContains('Finance', $labels);
        $this->assertContains('Messaging', $labels);
        $this->assertContains('Tax & ETIMS', $labels);
    }

    public function test_real_estate_operational_records_documents_and_exports_are_functional(): void
    {
        Storage::fake('public');

        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Records Estate',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
            'market_value' => 15000000,
        ])->assertRedirect();
        $property = Property::firstOrFail();

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'R-101',
            'occupancy_status' => 'Vacant',
            'rent_amount' => 60000,
        ])->assertRedirect();
        $unit = Unit::firstOrFail();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Records Tenant',
            'phone' => '+254700000011',
            'email' => 'records@example.test',
            'status' => 'Active',
        ])->assertRedirect();
        $tenant = Tenant::firstOrFail();

        $this->post(route('real-estate.service-requests.store'), [
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'assigned_to' => $this->admin->id,
            'request_type' => 'Repairs',
            'description' => 'Balcony door alignment',
            'status' => 'Assigned',
        ])->assertRedirect();
        $serviceRequest = ServiceRequest::firstOrFail();
        $this->assertMatchesRegularExpression('/^SRV-2026-\d{4}$/', $serviceRequest->request_number);
        $this->assertSame($this->admin->id, $serviceRequest->assigned_to);

        $this->post(route('real-estate.records.store', 'inspection'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'inspector_id' => $this->admin->id,
            'inspection_type' => 'Routine Inspection',
            'inspection_date' => '2026-08-10',
            'findings' => 'Clean unit',
            'recommendations' => 'Repaint balcony',
            'photos' => 'front.jpg, balcony.jpg',
            'status' => 'Completed',
        ])->assertRedirect();
        $inspection = Inspection::firstOrFail();
        $this->assertSame(['front.jpg', 'balcony.jpg'], $inspection->photos);

        $this->post(route('real-estate.records.store', 'valuation'), [
            'real_estate_property_id' => $property->id,
            'valuer_id' => $this->admin->id,
            'valuation_date' => '2026-08-11',
            'market_value' => 21000000,
            'rental_value' => '',
            'notes' => 'Comparable sales updated',
            'status' => 'Approved',
        ])->assertRedirect();
        $valuation = Valuation::firstOrFail();
        $this->assertSame(0.0, (float) $valuation->rental_value);
        $this->assertSame(21000000.0, (float) $property->fresh()->market_value);

        $this->post(route('real-estate.records.store', 'land'), [
            'real_estate_property_id' => $property->id,
            'parcel_number' => 'LR-555',
            'title_number' => 'TITLE-555',
            'land_size' => 1.25,
            'land_size_unit' => 'Acres',
            'zoning' => 'Residential',
            'ownership_status' => 'Owned',
            'ownership_history' => 'Original allocation, Transfer 2024',
            'sales_history' => 'Offer 2025',
        ])->assertRedirect();
        $land = LandParcel::firstOrFail();
        $this->assertSame(['Original allocation', 'Transfer 2024'], $land->ownership_history);

        $this->post(route('real-estate.records.store', 'development'), [
            'real_estate_property_id' => $property->id,
            'name' => 'Records Annex',
            'phase' => 'Phase 1',
            'budget' => '',
            'actual_cost' => '',
            'progress_percent' => 35,
            'contractor' => 'BuildCo',
            'status' => 'Construction',
        ])->assertRedirect();
        $development = DevelopmentProject::firstOrFail();
        $this->assertMatchesRegularExpression('/^DEV-2026-\d{4}$/', $development->development_number);
        $this->assertSame(35, (int) $development->progress_percent);

        $template = DocumentTemplate::create([
            'business_id' => $this->business->id,
            'name' => 'Lease Pack',
            'type' => 'Lease',
            'content' => 'Lease document',
            'output_format' => 'PDF',
            'is_active' => true,
        ]);

        $this->post(route('real-estate.documents.store'), [
            'documentable_type' => 'property',
            'documentable_id' => $property->id,
            'document_template_id' => $template->id,
            'document_type' => 'Lease Agreement',
            'title' => 'Records Estate Lease Pack',
            'status' => 'Active',
            'file' => UploadedFile::fake()->create('lease-pack.pdf', 12, 'application/pdf'),
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'documents']));
        $document = RealEstateDocument::firstOrFail();
        $this->assertSame($property->getMorphClass(), $document->documentable_type);
        Storage::disk('public')->assertExists($document->file_path);

        $this->get(route('real-estate.documents.download', $document))->assertOk();
        $this->get(route('real-estate.dashboard', ['section' => 'documents']))
            ->assertOk()
            ->assertSee('Document Vault')
            ->assertSee('Records Estate Lease Pack');

        foreach (['service-requests', 'inspections', 'valuations', 'land', 'development'] as $type) {
            $this->get(route('real-estate.reports.csv', $type))->assertOk()->assertDownload("real-estate-{$type}.csv");
            $this->getJson(route('api.v1.real-estate.reports.export', $type))->assertOk()->assertDownload("real-estate-{$type}.csv");
        }

        $this->postJson(route('api.v1.real-estate.service-requests.store'), [
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'request_type' => 'Cleaning Requests',
            'description' => 'Lobby clean-up',
        ])->assertCreated()->assertJsonPath('data.status', 'Open');
        $this->assertSame(2, ServiceRequest::count());

        $this->delete(route('real-estate.documents.destroy', $document))->assertRedirect();
        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertModelMissing($document);
    }

    public function test_utilities_amenities_and_tenant_statements_extend_real_estate_billing(): void
    {
        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Ledger Heights',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
        ])->assertRedirect();
        $property = Property::firstOrFail();

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'B-202',
            'unit_type' => 'One Bedroom',
            'occupancy_status' => 'Vacant',
            'rent_amount' => 45000,
        ])->assertRedirect();
        $unit = Unit::firstOrFail();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Utility Tenant',
            'phone' => '+254700000002',
            'email' => 'utility@example.test',
            'status' => 'Active',
        ])->assertRedirect();
        $tenant = Tenant::firstOrFail();

        $this->post(route('real-estate.leases.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'start_date' => '2026-07-01',
            'rent_amount' => 45000,
            'deposit_amount' => 45000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ])->assertRedirect();
        $lease = Lease::firstOrFail();

        $this->post(route('real-estate.leases.bill', $lease), [
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'due_date' => '2026-07-05',
        ])->assertRedirect();

        $this->post(route('real-estate.utility-types.store'), [
            'name' => 'Water',
            'billing_method' => 'Metered',
            'default_rate' => 120,
        ])->assertRedirect();
        $utilityType = UtilityType::firstOrFail();

        $this->post(route('real-estate.utility-meters.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_utility_type_id' => $utilityType->id,
            'meter_number' => 'WTR-B202',
            'meter_type' => 'Water Meter',
            'current_reading' => 100,
            'rate_per_unit' => 120,
        ])->assertRedirect();
        $meter = UtilityMeter::firstOrFail();

        $this->post(route('real-estate.utility-readings.store'), [
            'real_estate_utility_meter_id' => $meter->id,
            'current_reading' => 135,
            'reading_date' => '2026-07-31',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])->assertRedirect();

        $this->post(route('real-estate.amenities.store'), [
            'name' => 'Gym',
            'fee_type' => 'Fixed',
            'fee_amount' => 1500,
        ])->assertRedirect();

        $this->post(route('real-estate.amenity-bookings.store'), [
            'real_estate_amenity_id' => \Modules\RealEstate\Models\Amenity::firstOrFail()->id,
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_unit_id' => $unit->id,
            'booking_date' => '2026-07-15',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Confirmed',
        ])->assertRedirect();

        $this->post(route('real-estate.tenant-statements.store', $tenant), [
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])->assertRedirect();

        $this->assertDatabaseHas('real_estate_utility_bills', ['bill_number' => UtilityBill::firstOrFail()->bill_number, 'amount' => 4200]);
        $this->assertSame(3, TenantLedger::count());
        $this->assertSame(50700.0, (float) TenantStatement::firstOrFail()->outstanding_balance);
        $this->assertSame(3, Invoice::count());
        $this->assertInstanceOf(AmenityBooking::class, AmenityBooking::first());

        $this->getJson(route('api.v1.real-estate.tenants.ledger', $tenant))->assertOk()->assertJsonCount(3, 'data');
        $this->getJson(route('api.v1.real-estate.tenants.utilities', $tenant))->assertOk()->assertJsonPath('data.bills.0.amount', '4200.00');
        $this->get(route('real-estate.reports.csv', 'utility-billing'))->assertOk()->assertDownload('real-estate-utility-billing.csv');
    }

    public function test_permission_bootstrap_recovers_new_real_estate_utility_permissions(): void
    {
        $permission = IamPermission::where('name', 'real-estate.utilities.manage')->firstOrFail();
        \DB::table('iam_permission_role')->where('iam_permission_id', $permission->id)->delete();
        $permission->delete();

        $this->post(route('real-estate.utility-types.store'), [
            'name' => 'Garbage Collection',
            'billing_method' => 'Flat',
            'default_rate' => 500,
        ])->assertRedirect();

        $permission = IamPermission::where('name', 'real-estate.utilities.manage')->firstOrFail();
        $this->assertDatabaseHas('real_estate_utility_types', ['name' => 'Garbage Collection']);
        $this->assertTrue($this->admin->fresh()->hasPermission('real-estate.utilities.manage'));
        $this->assertDatabaseHas('iam_permission_role', ['iam_permission_id' => $permission->id]);
    }

    public function test_tenant_billing_email_alerts_send_on_configured_monthly_timing(): void
    {
        Mail::fake();

        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Email Plaza',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
        ])->assertRedirect();
        $property = Property::firstOrFail();

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'D-404',
            'occupancy_status' => 'Vacant',
            'rent_amount' => 50000,
        ])->assertRedirect();
        $unit = Unit::firstOrFail();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Email Tenant',
            'phone' => '+254700000004',
            'email' => 'old-email@example.test',
            'status' => 'Active',
        ])->assertRedirect();
        $tenant = Tenant::with('client')->firstOrFail();

        $this->post(route('real-estate.leases.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'start_date' => '2026-07-01',
            'rent_amount' => 50000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
        ])->assertRedirect();

        $this->post(route('real-estate.leases.bill', Lease::firstOrFail()), [
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'due_date' => '2026-08-01',
        ])->assertRedirect();

        $this->post(route('real-estate.tenants.billing-alerts.update', $tenant), [
            'email' => 'billing@example.test',
            'billing_alert_enabled' => 1,
            'billing_alert_frequency' => 'Monthly',
            'billing_alert_day' => 1,
            'billing_alert_subject' => 'Monthly rent reminder',
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'tenant-alerts']));

        $this->artisan('real-estate:billing-alerts --date=2026-08-01')
            ->expectsOutput('Real Estate billing alerts processed for 2026-08-01. Sent: 1.')
            ->assertExitCode(0);

        $tenant->refresh();
        $this->assertTrue($tenant->billing_alert_enabled);
        $this->assertNotNull($tenant->last_billing_alert_sent_at);
        $this->assertSame('billing@example.test', $tenant->client->fresh()->email);
        $this->assertDatabaseHas('email_logs', [
            'emailable_type' => $tenant->getMorphClass(),
            'emailable_id' => $tenant->id,
            'recipient_email' => 'billing@example.test',
            'subject' => 'Monthly rent reminder',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $tenant->getMorphClass(),
            'notifiable_id' => $tenant->id,
            'notification_type' => 'Real Estate Billing Alert',
            'delivery_channel' => 'Email',
            'status' => 'Delivered',
        ]);
    }

    public function test_sales_agents_and_listings_show_records_and_can_be_deleted(): void
    {
        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Sales Court',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
        ])->assertRedirect();
        $property = Property::firstOrFail();

        $this->post(route('real-estate.buyers.store'), [
            'name' => 'Sales Buyer',
            'email' => 'buyer@example.test',
            'status' => 'Buyer',
        ])->assertRedirect();
        $buyer = Buyer::firstOrFail();

        $this->post(route('real-estate.agents.store'), [
            'name' => 'Delete Ready Agent',
            'email' => 'agent@example.test',
            'status' => 'Active',
        ])->assertRedirect();
        $agent = Agent::firstOrFail();

        $this->post(route('real-estate.listings.store'), [
            'real_estate_property_id' => $property->id,
            'listing_type' => 'Sale',
            'price' => 3500000,
            'listing_date' => '2026-08-01',
            'status' => 'Draft',
        ])->assertRedirect();
        $listing = Listing::firstOrFail();

        $this->post(route('real-estate.sales.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_buyer_id' => $buyer->id,
            'sale_price' => 3500000,
            'deposit' => 500000,
            'status' => 'Reserved',
        ])->assertRedirect();
        $sale = Sale::firstOrFail();

        $this->get(route('real-estate.dashboard', ['section' => 'sales']))
            ->assertOk()
            ->assertSee('Delete Ready Agent')
            ->assertSee($listing->listing_number)
            ->assertSee($sale->sale_number);

        $this->delete(route('real-estate.listings.destroy', $listing))->assertRedirect(route('real-estate.dashboard', ['section' => 'sales']));
        $this->delete(route('real-estate.sales.destroy', $sale))->assertRedirect(route('real-estate.dashboard', ['section' => 'sales']));
        $this->delete(route('real-estate.agents.destroy', $agent))->assertRedirect(route('real-estate.dashboard', ['section' => 'sales']));

        $this->assertModelMissing($listing);
        $this->assertModelMissing($sale);
        $this->assertModelMissing($agent);
    }

    public function test_tenant_can_be_assigned_to_vacant_unit_and_unit_becomes_occupied(): void
    {
        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Tenant Booking Place',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
        ])->assertRedirect();
        $property = Property::firstOrFail();

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'E-505',
            'occupancy_status' => 'Vacant',
            'rent_amount' => 65000,
        ])->assertRedirect();
        $unit = Unit::firstOrFail();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Booked Tenant',
            'phone' => '+254700000005',
            'email' => 'booked@example.test',
            'status' => 'Prospect',
            'real_estate_unit_id' => $unit->id,
            'lease_start_date' => '2026-08-01',
            'assignment_billing_cycle' => 'Monthly',
        ])->assertRedirect();

        $tenant = Tenant::firstOrFail();
        $lease = Lease::firstOrFail();
        $this->assertSame('Active', $tenant->status);
        $this->assertSame($unit->id, $lease->real_estate_unit_id);
        $this->assertSame($tenant->id, $lease->real_estate_tenant_id);
        $this->assertSame(65000.0, (float) $lease->rent_amount);
        $this->assertSame('Occupied', $unit->fresh()->occupancy_status);

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'E-506',
            'occupancy_status' => 'Vacant',
            'rent_amount' => 70000,
        ])->assertRedirect();
        $secondUnit = Unit::where('unit_number', 'E-506')->firstOrFail();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Existing Assign Tenant',
            'phone' => '+254700000006',
            'email' => 'existing-assign@example.test',
            'status' => 'Prospect',
        ])->assertRedirect();
        $secondTenant = Tenant::whereHas('client', fn ($query) => $query->where('email', 'existing-assign@example.test'))->firstOrFail();

        $this->post(route('real-estate.tenants.unit-assignment.store', $secondTenant), [
            'real_estate_unit_id' => $secondUnit->id,
            'lease_start_date' => '2026-08-02',
            'assignment_rent_amount' => 72000,
            'assignment_billing_cycle' => 'Monthly',
        ])->assertRedirect(route('real-estate.dashboard', ['section' => 'tenants']));

        $this->assertSame('Occupied', $secondUnit->fresh()->occupancy_status);
        $this->assertSame(72000.0, (float) Lease::where('real_estate_unit_id', $secondUnit->id)->firstOrFail()->rent_amount);
    }

    public function test_tenant_offboarding_archives_history_releases_unit_and_blocks_delete(): void
    {
        $this->post(route('real-estate.properties.store'), [
            'property_name' => 'Archive Court',
            'property_type' => 'Apartment',
            'ownership_type' => 'Owned',
            'status' => 'Available',
        ])->assertRedirect();
        $property = Property::firstOrFail();

        $this->post(route('real-estate.units.store'), [
            'real_estate_property_id' => $property->id,
            'unit_number' => 'C-303',
            'occupancy_status' => 'Vacant',
            'rent_amount' => 55000,
        ])->assertRedirect();
        $unit = Unit::firstOrFail();

        $this->post(route('real-estate.tenants.store'), [
            'name' => 'Archive Tenant',
            'phone' => '+254700000003',
            'email' => 'archive@example.test',
            'status' => 'Active',
        ])->assertRedirect();
        $tenant = Tenant::firstOrFail();

        $this->post(route('real-estate.leases.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'start_date' => '2026-07-01',
            'rent_amount' => 55000,
            'billing_cycle' => 'Monthly',
            'status' => 'Active',
            'auto_billing' => 1,
        ])->assertRedirect();

        $this->post(route('real-estate.maintenance.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'maintenance_type' => 'Corrective',
            'priority' => 'Medium',
            'description' => 'Move-out repairs',
            'status' => 'Open',
        ])->assertRedirect();

        $this->post(route('real-estate.utility-types.store'), [
            'name' => 'Electricity',
            'billing_method' => 'Metered',
            'default_rate' => 30,
        ])->assertRedirect();

        $this->post(route('real-estate.utility-meters.store'), [
            'real_estate_property_id' => $property->id,
            'real_estate_unit_id' => $unit->id,
            'real_estate_tenant_id' => $tenant->id,
            'real_estate_utility_type_id' => UtilityType::firstOrFail()->id,
            'meter_number' => 'EL-C303',
            'meter_type' => 'Electricity Meter',
            'current_reading' => 200,
            'rate_per_unit' => 30,
        ])->assertRedirect();

        $this->post(route('real-estate.tenants.notice', $tenant), [
            'notice_date' => '2026-07-10',
            'move_out_date' => '2026-07-31',
        ])->assertRedirect();

        $this->post(route('real-estate.tenants.archive', $tenant), [
            'move_out_date' => '2026-07-31',
        ])->assertRedirect();

        $tenant->refresh();
        $this->assertSame('Archived', $tenant->status);
        $this->assertSame('Archive Tenant', $tenant->offboarding_step);
        $this->assertSame('Vacant', $unit->refresh()->occupancy_status);
        $this->assertSame('Terminated', Lease::firstOrFail()->status);
        $this->assertFalse((bool) Lease::firstOrFail()->auto_billing);
        $this->assertNull(UtilityMeter::firstOrFail()->real_estate_tenant_id);
        $this->assertSame('Inactive', UtilityMeter::firstOrFail()->status);
        $this->assertSame('Closed', \Modules\RealEstate\Models\MaintenanceRequest::firstOrFail()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['event' => 'real-estate.tenant.archived']);

        $super = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($super)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);

        $this->delete(route('real-estate.tenants.destroy', $tenant), ['confirm_delete' => 1])
            ->assertStatus(422)
            ->assertSee('Tenant cannot be deleted because historical records exist. Archive the tenant instead.');

        $clean = Tenant::create([
            'client_id' => \App\Models\Client::create(['name' => 'Clean Tenant', 'type' => 'individual'])->id,
            'tenant_number' => 'TEN-CLEAN',
            'status' => 'Prospect',
        ]);

        $this->delete(route('real-estate.tenants.destroy', $clean), ['confirm_delete' => 1])->assertRedirect();
        $this->assertModelMissing($clean);
        $this->assertDatabaseHas('admin_audit_logs', ['event' => 'real-estate.tenant.deleted']);
        $this->get(route('real-estate.reports.csv', 'archived-tenants'))->assertOk()->assertDownload('real-estate-archived-tenants.csv');
    }
}
