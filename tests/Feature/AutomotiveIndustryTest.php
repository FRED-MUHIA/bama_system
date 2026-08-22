<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Automotive\Models\PartRequestItem;
use Modules\Automotive\Models\Vehicle;
use Modules\Automotive\Services\AutomotiveEstimateService;
use Modules\Automotive\Services\AutomotiveFinanceService;
use Modules\Automotive\Services\AutomotiveInventoryService;
use Modules\Automotive\Services\BookingService;
use Modules\Automotive\Services\FleetService;
use Modules\Automotive\Services\InspectionService;
use Modules\Automotive\Services\JobCardService;
use Modules\Automotive\Services\QualityControlService;
use Modules\Automotive\Services\VehicleCheckInService;
use Modules\Automotive\Services\VehicleService;
use Modules\Automotive\Services\WarrantyService;
use Tests\TestCase;

class AutomotiveIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformTenant $tenant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Auto Prime',
            'slug' => 'auto-prime',
            'industry' => 'automotive',
            'sub_industry' => 'vehicle-repair-garage',
            'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);

        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Auto Prime',
            'slug' => 'auto-prime',
            'industry' => 'automotive',
            'is_active' => true,
        ]);
        ActiveBusiness::switchTo($this->business);
        app(ModuleRegistry::class)->enableDefaultsFor($this->tenant);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'status' => 'Active',
            'current_tenant_id' => $this->tenant->id,
        ]);

        $this->actingAs($this->admin)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);
        app(IamService::class)->bootstrap();
    }

    public function test_automotive_package_navigation_dashboard_and_permissions_are_available(): void
    {
        $this->getJson('/api/v1/industry-packages/automotive?sub_industry=vehicle-repair-garage')
            ->assertOk()
            ->assertJsonPath('slug', 'automotive')
            ->assertJsonPath('industry', 'Automotive')
            ->assertJsonPath('selected_sub_industry', 'vehicle-repair-garage')
            ->assertJsonFragment(['Vehicle Management'])
            ->assertJsonFragment(['Automotive Administrator']);

        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Vehicle Management', $labels);
        $this->assertContains('Job Cards', $labels);
        $this->assertContains('Parts & Inventory', $labels);

        $this->get(route('automotive.dashboard'))
            ->assertOk()
            ->assertSee('Automotive')
            ->assertSee('Open job cards');

        $this->get(route('dashboard'))
            ->assertRedirect(route('automotive.dashboard'));
    }

    public function test_all_visible_automotive_modules_have_working_destinations(): void
    {
        $routes = [
            ['automotive.dashboard', []],
            ['clients.index', []],
            ['automotive.vehicles', []],
            ['automotive.workshop', []],
            ['automotive.job-cards', []],
            ['automotive.bookings', []],
            ['automotive.inspections', []],
            ['automotive.estimates', []],
            ['automotive.parts', []],
            ['erp.procurement', []],
            ['automotive.sales', []],
            ['invoices.index', []],
            ['automotive.quality', []],
            ['automotive.warranty', []],
            ['automotive.fleet', []],
            ['automotive.reports', []],
            ['communication.center', []],
            ['automotive.labour-operations', []],
            ['automotive.technicians', []],
            ['automotive.road-tests', []],
            ['automotive.vehicle-release', []],
            ['automotive.job-costing', []],
            ['automotive.service-reminders', []],
            ['automotive.specialty', ['type' => 'tyres']],
            ['automotive.specialty', ['type' => 'body-paint']],
            ['automotive.specialty', ['type' => 'insurance-repairs']],
            ['automotive.customer-service', []],
        ];

        foreach ($routes as [$name, $parameters]) {
            $this->get(route($name, $parameters ?? []), ['HTTP_REFERER' => route('automotive.dashboard')])
                ->assertOk();
        }
    }

    public function test_extended_automotive_forms_create_tenant_scoped_records(): void
    {
        $client = Client::create(['name' => 'Express Motors', 'type' => 'company']);
        $vehicle = app(VehicleService::class)->create([
            'client_id' => $client->id,
            'registration_number' => 'KDG 987C',
            'vin' => 'VIN-AUTO-987',
            'make' => 'Nissan',
            'model' => 'Navara',
            'mileage' => 53000,
        ]);
        $job = app(JobCardService::class)->create([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'technician_id' => $this->admin->id,
            'work_requested' => 'Diagnostics and repair',
            'status' => 'Diagnosis',
        ]);

        $this->post(route('automotive.estimates.store'), [
            'job_card_id' => $job->id,
            'vehicle_id' => $vehicle->id,
            'client_id' => $client->id,
            'items' => [
                ['type' => 'Labour', 'description' => 'Diagnostic labour', 'quantity' => 1, 'unit_price' => 2500, 'tax_rate' => 0],
                ['type' => 'Part', 'description' => '', 'quantity' => '', 'unit_price' => '', 'tax_rate' => ''],
            ],
        ])->assertRedirect(route('automotive.estimates'))->assertSessionHasNoErrors();

        $this->post(route('automotive.labour-operations.store'), [
            'labour_code' => 'LAB-DIAG',
            'name' => 'Computer diagnostics',
            'standard_hours' => 1.5,
            'hourly_rate' => 3000,
            'skill_required' => 'Diagnostic Technician',
        ])->assertRedirect(route('automotive.labour-operations'))->assertSessionHasNoErrors();

        $this->post(route('automotive.job-cards.costing', $job), [
            'parts_cost' => 1000,
            'labour_cost' => 1500,
            'outsourced_cost' => 500,
            'revenue' => 6000,
        ])->assertRedirect(route('automotive.job-costing'))->assertSessionHasNoErrors();

        $this->post(route('automotive.service-reminders.store'), [
            'vehicle_id' => $vehicle->id,
            'type' => 'Service Due',
            'due_date' => today()->addMonth()->toDateString(),
            'due_mileage' => 58000,
            'status' => 'Open',
        ])->assertRedirect(route('automotive.service-reminders'))->assertSessionHasNoErrors();

        $this->post(route('automotive.specialty.store', 'tyres'), [
            'vehicle_id' => $vehicle->id,
            'job_card_id' => $job->id,
            'status' => 'Open',
            'payload' => [
                'reference' => 'TYRE-INSPECT',
                'work_scope' => 'Tyre inspection and balancing',
                'cost_estimate' => 3200,
            ],
        ])->assertRedirect(route('automotive.specialty', 'tyres'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('automotive_estimates', ['tenant_id' => $this->tenant->id, 'business_id' => $this->business->id, 'total' => 2500]);
        $this->assertDatabaseHas('automotive_labour_operations', ['tenant_id' => $this->tenant->id, 'business_id' => $this->business->id, 'labour_code' => 'LAB-DIAG']);
        $this->assertDatabaseHas('automotive_job_costs', ['tenant_id' => $this->tenant->id, 'business_id' => $this->business->id, 'job_card_id' => $job->id, 'gross_profit' => 3000]);
        $this->assertDatabaseHas('automotive_service_reminders', ['tenant_id' => $this->tenant->id, 'business_id' => $this->business->id, 'vehicle_id' => $vehicle->id, 'status' => 'Open']);
        $this->assertDatabaseHas('automotive_specialty_records', ['tenant_id' => $this->tenant->id, 'business_id' => $this->business->id, 'type' => 'tyres', 'status' => 'Open']);
    }

    public function test_automotive_workflow_parts_finance_quality_warranty_and_tenant_isolation_work(): void
    {
        $client = Client::create(['name' => 'Mambo Logistics', 'type' => 'company', 'email' => 'mambo@example.test']);

        $vehicle = app(VehicleService::class)->create([
            'client_id' => $client->id,
            'registration_number' => 'KDA 123A',
            'vin' => 'VIN-AUTO-001',
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'mileage' => 42000,
            'status' => 'Active',
        ]);
        $this->assertSame($this->tenant->id, $vehicle->tenant_id);
        $this->assertSame($this->business->id, $vehicle->business_id);

        $this->expectException(ValidationException::class);
        app(VehicleService::class)->create([
            'client_id' => $client->id,
            'registration_number' => 'KDA 123A',
            'vin' => 'VIN-AUTO-002',
        ]);
    }

    public function test_complete_job_card_lifecycle_can_be_completed(): void
    {
        $client = Client::create(['name' => 'City Shuttle', 'type' => 'company']);
        $vehicle = app(VehicleService::class)->create([
            'client_id' => $client->id,
            'registration_number' => 'KCB 456B',
            'vin' => 'VIN-AUTO-456',
            'make' => 'Isuzu',
            'model' => 'NPR',
            'mileage' => 81000,
        ]);

        $booking = app(BookingService::class)->create([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'requested_service' => 'Full service and brake inspection',
            'preferred_date' => today(),
            'status' => 'Confirmed',
        ]);
        $this->assertMatchesRegularExpression('/^BK-2026-\d{5}$/', $booking->booking_number);

        $checkIn = app(VehicleCheckInService::class)->create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'mileage' => 81200,
            'fuel_level' => 'Half',
            'keys_received' => true,
        ]);
        $this->assertMatchesRegularExpression('/^CHK-2026-\d{5}$/', $checkIn->check_in_number);
        $this->assertSame('In Workshop', $vehicle->fresh()->status);

        $job = app(JobCardService::class)->create([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'booking_id' => $booking->id,
            'check_in_id' => $checkIn->id,
            'technician_id' => $this->admin->id,
            'work_requested' => 'Service, diagnostics, brake pads',
            'status' => 'Diagnosis',
        ]);
        $this->assertMatchesRegularExpression('/^JC-2026-\d{5}$/', $job->job_number);

        $inspection = app(InspectionService::class)->create([
            'vehicle_id' => $vehicle->id,
            'check_in_id' => $checkIn->id,
            'job_card_id' => $job->id,
            'technician_id' => $this->admin->id,
            'inspection_date' => today(),
            'recommendations' => 'Replace brake pads',
            'estimated_cost' => 18000,
        ]);
        $this->assertMatchesRegularExpression('/^INS-2026-\d{5}$/', $inspection->inspection_number);

        $estimate = app(AutomotiveEstimateService::class)->create([
            'job_card_id' => $job->id,
            'vehicle_id' => $vehicle->id,
            'client_id' => $client->id,
            'status' => 'Draft',
        ], [
            ['type' => 'Labour', 'description' => 'Service labour', 'quantity' => 2, 'unit_price' => 3500, 'tax_rate' => 0],
            ['type' => 'Part', 'description' => 'Brake pads', 'quantity' => 1, 'unit_price' => 8500, 'tax_rate' => 0],
        ]);
        $estimate = app(AutomotiveEstimateService::class)->approve($estimate);
        $quotation = app(AutomotiveEstimateService::class)->toQuotation($estimate);
        $this->assertSame($quotation->id, $estimate->fresh()->quotation_id);
        $this->assertSame(15500.0, (float) $estimate->total);

        $part = app(AutomotiveInventoryService::class)->part([
            'part_number' => 'BRK-001',
            'name' => 'Brake Pads',
            'category' => 'Brake System',
            'cost_price' => 4500,
            'selling_price' => 8500,
            'stock_quantity' => 4,
            'reorder_level' => 1,
        ]);
        $request = app(AutomotiveInventoryService::class)->request($job, ['requested_by' => $this->admin->id], [[
            'part_id' => $part->id,
            'requested_qty' => 1,
        ]]);
        $item = $request->items()->firstOrFail();
        $issued = app(AutomotiveInventoryService::class)->issue($item, 1);
        $this->assertInstanceOf(PartRequestItem::class, $issued);
        $this->assertSame(3.0, (float) $part->fresh()->stock_quantity);

        app(JobCardService::class)->addLabourTask($job, [
            'technician_id' => $this->admin->id,
            'description' => 'Service labour',
            'billable_hours' => 2,
            'hourly_rate' => 3500,
            'status' => 'Completed',
        ]);
        $invoice = app(AutomotiveFinanceService::class)->invoiceJob($job->fresh());
        $cost = app(AutomotiveFinanceService::class)->costing($job->fresh(), ['revenue' => $invoice->total]);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame('automotive', $invoice->industry_module);
        $this->assertSame($job->job_number, $invoice->industry_reference);
        $this->assertGreaterThan(0, (float) $cost->gross_profit);

        app(QualityControlService::class)->quality($job->fresh(), ['inspector_id' => $this->admin->id, 'result' => 'Pass']);
        app(QualityControlService::class)->roadTest($job->fresh(), ['tester_id' => $this->admin->id, 'test_result' => 'Passed', 'start_mileage' => 81200, 'end_mileage' => 81208]);
        $job = app(JobCardService::class)->updateStatus($job->fresh(), 'Ready for Collection');
        $release = app(QualityControlService::class)->release($job, ['payment_status' => 'unpaid', 'override_unpaid' => true, 'customer_name' => 'Fleet supervisor']);
        $this->assertMatchesRegularExpression('/^REL-2026-\d{5}$/', $release->release_number);

        $warranty = app(WarrantyService::class)->warranty([
            'job_card_id' => $job->id,
            'vehicle_id' => $vehicle->id,
            'part_id' => $part->id,
            'type' => 'Parts',
            'warranty_start' => today(),
            'warranty_end' => today()->addMonths(6),
            'status' => 'Active',
        ]);
        $comeback = app(WarrantyService::class)->comeback($job, ['complaint' => 'Brake noise returned', 'warranty' => true]);
        $fleet = app(FleetService::class)->create(['client_id' => $client->id, 'name' => 'City Shuttle Fleet']);

        $this->assertMatchesRegularExpression('/^WRN-2026-\d{5}$/', $warranty->warranty_number);
        $this->assertMatchesRegularExpression('/^CMP-2026-\d{5}$/', $comeback->comeback_number);
        $this->assertMatchesRegularExpression('/^FLT-2026-\d{5}$/', $fleet->fleet_number);

        $otherTenant = PlatformTenant::create([
            'name' => 'Other Auto',
            'slug' => 'other-auto',
            'industry' => 'automotive',
            'status' => 'active',
        ]);
        $otherBusiness = Business::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Auto',
            'slug' => 'other-auto',
            'industry' => 'automotive',
            'is_active' => true,
        ]);
        ActiveTenant::switchTo($otherTenant);
        ActiveBusiness::switchTo($otherBusiness);
        app(VehicleService::class)->create(['registration_number' => 'HIDDEN 001']);

        ActiveTenant::switchTo($this->tenant);
        ActiveBusiness::switchTo($this->business);
        $this->assertSame(1, Vehicle::count());
        $this->assertSame(2, Vehicle::withoutGlobalScopes()->count());
    }
}
