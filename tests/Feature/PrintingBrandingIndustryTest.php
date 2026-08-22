<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\IamRole;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Tenant as PlatformTenant;
use App\Models\User;
use App\Services\IamService;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Services\TenantProvisioningService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\PrintingBranding\Models\Artwork;
use Modules\PrintingBranding\Models\DeliveryNote;
use Modules\PrintingBranding\Models\Dispatch;
use Modules\PrintingBranding\Models\Estimate;
use Modules\PrintingBranding\Models\FinishingOption;
use Modules\PrintingBranding\Models\JobCost;
use Modules\PrintingBranding\Models\JobTicket;
use Modules\PrintingBranding\Models\Machine;
use Modules\PrintingBranding\Models\MachineMaintenance;
use Modules\PrintingBranding\Models\Material;
use Modules\PrintingBranding\Models\MaterialReservation;
use Modules\PrintingBranding\Models\OutsourcingOrder;
use Modules\PrintingBranding\Models\PricingRule;
use Modules\PrintingBranding\Models\PrintMethod;
use Modules\PrintingBranding\Models\ProductTemplate;
use Modules\PrintingBranding\Models\ProductionJob;
use Modules\PrintingBranding\Models\ProductionOperation;
use Modules\PrintingBranding\Models\ProductionSchedule;
use Modules\PrintingBranding\Models\ProofApproval;
use Modules\PrintingBranding\Models\QualityCheck;
use Modules\PrintingBranding\Models\Waste;
use Modules\PrintingBranding\Services\ArtworkService;
use Modules\PrintingBranding\Services\DispatchService;
use Modules\PrintingBranding\Services\EstimateService;
use Modules\PrintingBranding\Services\PrintCostingService;
use Modules\PrintingBranding\Services\ProductionJobService;
use Modules\PrintingBranding\Services\ProductionSchedulingService;
use Modules\PrintingBranding\Services\ProofApprovalService;
use Modules\PrintingBranding\Services\QualityControlService;
use Modules\PrintingBranding\Services\WasteService;
use Tests\TestCase;

class PrintingBrandingIndustryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PlatformTenant $tenant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = PlatformTenant::create([
            'name' => 'Print Prime',
            'slug' => 'print-prime',
            'industry' => 'printing_branding',
            'sub_industry' => 'enterprise',
            'status' => 'active',
        ]);
        ActiveTenant::switchTo($this->tenant);

        $this->business = Business::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Print Prime',
            'slug' => 'print-prime',
            'industry' => 'printing_branding',
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

    public function test_printing_branding_package_registration_provisioning_and_navigation_are_available(): void
    {
        $this->getJson('/api/v1/industry-packages/printing_branding?sub_industry=dtf-printing')
            ->assertOk()
            ->assertJsonPath('slug', 'printing_branding')
            ->assertJsonPath('industry', 'Printing & Branding')
            ->assertJsonPath('selected_sub_industry', 'dtf-printing')
            ->assertJsonFragment(['name' => 'DTF Printing'])
            ->assertJsonFragment(['Artwork Management'])
            ->assertJsonFragment(['Printing Administrator']);

        $owner = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $tenant = app(TenantProvisioningService::class)->provision([
            'tenant_name' => 'DTF House',
            'business_name' => 'DTF House',
            'industry' => 'printing_branding',
            'sub_industry' => 'dtf-printing',
            'plan' => 'starter',
        ], $owner);

        $settings = $tenant->fresh()->settings;
        $this->assertSame('dtf-printing', $settings['printing_branding_configuration']['sub_industry']);
        $this->assertContains('Film Consumption', $settings['printing_branding_configuration']['enabled_modules']);
        $this->assertTrue($settings['printing_branding_configuration']['client_portal_approval_enabled']);

        ActiveTenant::switchTo($this->tenant);
        ActiveBusiness::switchTo($this->business);
        $labels = app(NavigationManager::class)->sidebar()->pluck('label')->all();
        $this->assertContains('Dashboard', $labels);
        $this->assertContains('Estimating', $labels);
        $this->assertContains('Jobs', $labels);
        $this->assertContains('Artwork', $labels);
        $this->assertContains('Dispatch', $labels);

        $this->get(route('printing-branding.dashboard'))
            ->assertOk()
            ->assertSee('Printing & Branding')
            ->assertDontSee('Electronic Job Tickets');

        $this->get(route('printing-branding.jobs'))
            ->assertOk()
            ->assertSee('Add client')
            ->assertSee('Creates a shared CRM client')
            ->assertSee('Dimensions');
    }

    public function test_printing_workflow_from_estimate_to_invoice_and_profitability_works(): void
    {
        $client = Client::create(['name' => 'Acme Brands', 'type' => 'company', 'email' => 'buyer@example.test']);
        $product = Product::create(['name' => 'Corporate T-Shirt', 'sku' => 'TSHIRT-001', 'price' => 0, 'cost_price' => 300, 'stock_quantity' => 100, 'reorder_level' => 10, 'stock_unit' => 'pcs', 'is_active' => true]);
        $material = Material::create([
            'product_id' => $product->id,
            'material_code' => 'MAT-TS-001',
            'name' => 'White T-Shirts',
            'category' => 'T-Shirts',
            'unit' => 'pcs',
            'unit_cost' => 300,
            'stock_quantity' => 100,
            'reorder_level' => 10,
            'status' => 'Active',
        ]);
        $machine = Machine::create([
            'machine_code' => 'DTF-001',
            'name' => 'DTF Printer 1',
            'machine_type' => 'DTF Printer',
            'cost_per_hour' => 1500,
            'status' => 'Available',
        ]);

        $estimate = app(EstimateService::class)->create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'product_name' => 'DTF Corporate T-Shirts',
            'quantity' => 50,
            'specifications' => ['Garment Size' => 'Mixed', 'Print Position' => 'Front'],
            'material_cost' => 15000,
            'machine_cost' => 3000,
            'labor_cost' => 2500,
            'artwork_charges' => 1000,
            'markup' => 40,
        ]);
        $this->assertMatchesRegularExpression('/^EST-2026-\d{5}$/', $estimate->estimate_number);
        $this->assertSame(30100.0, (float) $estimate->selling_price);

        $quotation = app(EstimateService::class)->convertToQuotation($estimate);
        $this->assertInstanceOf(Quotation::class, $quotation);
        $this->assertSame($quotation->id, $estimate->fresh()->quotation_id);

        $job = app(ProductionJobService::class)->create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'estimate_id' => $estimate->id,
            'product_id' => $product->id,
            'machine_id' => $machine->id,
            'product_name' => 'DTF Corporate T-Shirts',
            'quantity' => 50,
            'specifications' => ['Garment Size' => 'Mixed', 'Print Position' => 'Front'],
            'materials_required' => [['material_id' => $material->id, 'quantity' => 50]],
            'delivery_date' => '2026-08-28',
            'priority' => 'Urgent',
            'status' => 'Approved',
        ]);

        $this->assertMatchesRegularExpression('/^JOB-2026-\d{5}$/', $job->job_number);
        $this->assertInstanceOf(JobTicket::class, $job->ticket);
        $this->assertSame(50.0, (float) $material->fresh()->reserved_quantity);
        $reservation = MaterialReservation::firstOrFail();

        app(ProductionJobService::class)->consumeMaterial($reservation, 40);
        $this->assertSame(60.0, (float) $material->fresh()->stock_quantity);
        $this->assertSame(60.0, (float) $product->fresh()->stock_quantity);

        $cost = app(PrintCostingService::class)->calculate($job, [
            'machine_cost' => 3000,
            'labor_cost' => 2500,
            'artwork_cost' => 1000,
            'finishing_cost' => 1500,
            'selling_price' => 30100,
        ]);
        $this->assertInstanceOf(JobCost::class, $cost);
        $this->assertSame(10100.0, (float) $cost->gross_profit);

        $artwork = app(ArtworkService::class)->uploadVersion($job, ['file_path' => 'printing/artwork/v1.pdf', 'status' => 'Received']);
        $artworkV2 = app(ArtworkService::class)->uploadVersion($job, ['file_path' => 'printing/artwork/v2.pdf', 'status' => 'Proof Ready']);
        $this->assertSame($artwork->artwork_number, $artworkV2->artwork_number);
        $this->assertSame(2, $artworkV2->version);

        $proof = app(ProofApprovalService::class)->sendToClient($artworkV2);
        $approved = app(ProofApprovalService::class)->decide($proof, 'approve', 'Approved by client portal');
        $this->assertInstanceOf(ProofApproval::class, $approved);
        $this->assertSame('Approved', $artworkV2->fresh()->status);

        app(ProductionSchedulingService::class)->schedule($job, [
            'machine_id' => $machine->id,
            'starts_at' => '2026-08-24 09:00:00',
            'ends_at' => '2026-08-24 11:00:00',
            'status' => 'Scheduled',
        ]);
        $this->assertInstanceOf(ProductionSchedule::class, ProductionSchedule::first());

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ProductionSchedulingService::class)->schedule($job, [
            'machine_id' => $machine->id,
            'starts_at' => '2026-08-24 10:00:00',
            'ends_at' => '2026-08-24 12:00:00',
            'status' => 'Scheduled',
        ]);
    }

    public function test_quality_waste_dispatch_delivery_note_invoice_reports_and_reorder_work(): void
    {
        $client = Client::create(['name' => 'Event Agency', 'type' => 'company']);
        $job = app(ProductionJobService::class)->create([
            'client_id' => $client->id,
            'product_name' => 'Pull-Up Banner',
            'quantity' => 5,
            'delivery_date' => '2026-08-25',
            'priority' => 'Normal',
            'status' => 'Quality Control',
        ]);
        app(PrintCostingService::class)->calculate($job, ['actual_material_cost' => 5000, 'machine_cost' => 2000, 'labor_cost' => 1000, 'selling_price' => 15000]);

        $check = app(QualityControlService::class)->inspect($job, [
            'inspector_id' => $this->admin->id,
            'checkpoints' => ['Print Color' => 'Pass', 'Alignment' => 'Pass'],
            'result' => 'Pass',
            'notes' => 'Ready',
        ]);
        $this->assertInstanceOf(QualityCheck::class, $check);
        $this->assertSame('Ready for Dispatch', $job->fresh()->status);

        $waste = app(WasteService::class)->record([
            'job_id' => $job->id,
            'waste_type' => 'Setup Waste',
            'quantity' => 1,
            'cost' => 250,
            'reason' => 'Calibration',
        ]);
        $this->assertInstanceOf(Waste::class, $waste);

        ProductionOperation::create([
            'job_id' => $job->id,
            'stage' => 'Printing',
            'quantity_produced' => 5,
            'quantity_rejected' => 0,
            'status' => 'Completed',
            'started_at' => '2026-08-25 09:00:00',
            'completed_at' => '2026-08-25 10:00:00',
        ]);

        $dispatch = app(DispatchService::class)->dispatch($job, [
            'status' => 'Delivered',
            'delivery_address' => 'Westlands',
            'dispatch_date' => '2026-08-25',
            'delivery_date' => '2026-08-25',
            'receiver_name' => 'Jane',
        ]);
        $this->assertInstanceOf(Dispatch::class, $dispatch);
        $note = app(DispatchService::class)->deliveryNote($dispatch);
        $this->assertInstanceOf(DeliveryNote::class, $note);
        $this->assertMatchesRegularExpression('/^DN-2026-\d{5}$/', $note->delivery_note_number);

        $invoice = app(ProductionJobService::class)->invoice($job, 'Final Invoice');
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame('printing_branding', $invoice->industry_module);
        $this->assertSame($job->job_number, $invoice->industry_reference);
        $this->assertSame($job->id, data_get($invoice->industry_context, 'production_job_id'));
        $this->assertSame('Pull-Up Banner', data_get($invoice->industry_context, 'product_name'));
        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Printing Job')
            ->assertSee('Production Context')
            ->assertSee('Pull-Up Banner');

        $this->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('Printing production job')
            ->assertSee($job->job_number);

        $this->post(route('invoices.store'), [
            'client_mode' => 'existing',
            'client_id' => $client->id,
            'printing_job_id' => $job->id,
            'printing_invoice_type' => 'Deposit Invoice',
            'invoice_date' => '2026-08-25',
            'due_date' => '2026-08-30',
            'items' => [[
                'title' => 'Pull-Up Banner',
                'description' => 'Deposit for '.$job->job_number,
                'quantity' => 5,
                'unit_price' => 1000,
                'discount' => 0,
                'tax_rate' => 0,
            ]],
        ])->assertRedirect();

        $manualInvoice = Invoice::where('industry_reference', $job->job_number)->latest('id')->firstOrFail();
        $this->assertSame('Deposit Invoice', data_get($manualInvoice->industry_context, 'invoice_type'));
        $this->assertSame($job->id, data_get($manualInvoice->industry_context, 'production_job_id'));

        $this->get(route('printing-branding.reports.csv', 'jobs'))->assertOk()->assertDownload('printing-jobs.csv');
        $this->get(route('printing-branding.reports', ['date' => '2026-08-25']))
            ->assertOk()
            ->assertSee('Daily production')
            ->assertSee('Pull-Up Banner')
            ->assertSee('Printing')
            ->assertSee('Produced 5.000');
        $this->get(route('printing-branding.reports.csv', ['type' => 'daily-production', 'date' => '2026-08-25']))
            ->assertOk()
            ->assertDownload('printing-daily-production.csv');
        $this->getJson(route('api.v1.printing-branding.dashboard'))->assertOk()->assertJsonPath('data.metrics.Jobs Open', 0);
        $this->getJson(route('api.v1.printing-branding.mobile.jobs.show', $job))->assertOk()->assertJsonFragment(['Start Job']);

        $this->post(route('printing-branding.jobs.reorder', $job))->assertRedirect(route('printing-branding.jobs'));
        $this->assertSame(2, ProductionJob::count());
    }

    public function test_client_can_be_added_from_printing_jobs_screen(): void
    {
        $this->post(route('printing-branding.clients.store'), [
            'name' => 'Brand New Client',
            'client_type' => 'Corporate',
            'phone' => '+254700000000',
            'email' => 'brand@example.test',
            'company_name' => 'Brand New Client Ltd',
            'lead_source' => 'Referral',
            'print_frequency' => 'Monthly',
            'price_tier' => 'Corporate',
            'credit_limit' => 50000,
        ])->assertRedirect(route('printing-branding.jobs'));

        $client = Client::where('email', 'brand@example.test')->firstOrFail();
        $this->assertSame('company', $client->type);
        $this->assertDatabaseHas('printing_client_profiles', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'client_type' => 'Corporate',
            'price_tier' => 'Corporate',
        ]);

        $this->get(route('printing-branding.jobs'))->assertOk()->assertSee('Brand New Client');
    }

    public function test_production_job_form_stores_quantity_and_dimensions_in_specifications(): void
    {
        $client = Client::create(['name' => 'Specs Client', 'type' => 'company']);

        $this->post(route('printing-branding.jobs.store'), [
            'client_id' => $client->id,
            'product_name' => 'Business Cards',
            'quantity' => 500,
            'delivery_date' => '2026-08-29',
            'status' => 'Draft',
            'specifications' => [
                'Dimensions' => '90 x 50 mm',
                'Material' => '300gsm Art Card',
                'Print Method' => 'Digital Printing',
                'Sides' => 'Double',
            ],
        ])->assertRedirect(route('printing-branding.jobs'));

        $job = ProductionJob::where('product_name', 'Business Cards')->firstOrFail();
        $this->assertSame('500', $job->specifications['Quantity']);
        $this->assertSame('90 x 50 mm', $job->specifications['Dimensions']);
        $this->assertSame('300gsm Art Card', $job->specifications['Material']);
    }

    public function test_sidebar_features_have_working_create_and_update_actions(): void
    {
        $client = Client::create(['name' => 'Workflow Client', 'type' => 'company']);
        $job = app(ProductionJobService::class)->create([
            'client_id' => $client->id,
            'product_name' => 'A1 Poster',
            'quantity' => 10,
            'status' => 'Draft',
        ]);

        $this->post(route('printing-branding.machines.store'), [
            'machine_code' => 'MCH-001',
            'name' => 'Digital Printer',
            'machine_type' => 'Digital Printer',
            'cost_per_hour' => 1200,
            'status' => 'Available',
        ])->assertRedirect();
        $machine = Machine::where('machine_code', 'MCH-001')->firstOrFail();

        $this->post(route('printing-branding.machines.maintenance.store', $machine), [
            'maintenance_type' => 'Preventive Service',
            'service_date' => '2026-08-22',
            'next_service_date' => '2026-09-22',
        ])->assertRedirect();
        $this->assertInstanceOf(MachineMaintenance::class, MachineMaintenance::first());

        $this->post(route('printing-branding.materials.store'), [
            'material_code' => 'PAPER-A1',
            'name' => 'A1 Poster Paper',
            'category' => 'Paper',
            'unit' => 'sheet',
            'stock_quantity' => 100,
            'unit_cost' => 50,
            'reorder_level' => 10,
        ])->assertRedirect();
        $material = Material::where('material_code', 'PAPER-A1')->firstOrFail();

        $job->update(['materials_required' => [['material_id' => $material->id, 'quantity' => 10]]]);
        app(ProductionJobService::class)->updateStatus($job, 'Approved');
        $reservation = MaterialReservation::firstOrFail();
        $this->post(route('printing-branding.materials.consume', $reservation), ['quantity' => 5])->assertRedirect();
        $this->assertSame(95.0, (float) $material->fresh()->stock_quantity);

        $this->post(route('printing-branding.operations.store'), [
            'job_id' => $job->id,
            'machine_id' => $machine->id,
            'operator_id' => $this->admin->id,
            'stage' => 'Printing',
            'quantity_produced' => 8,
            'quantity_rejected' => 2,
        ])->assertRedirect();
        $operation = ProductionOperation::firstOrFail();
        $this->post(route('printing-branding.operations.update', $operation), ['action' => 'complete'])->assertRedirect();
        $this->assertSame('Completed', $operation->fresh()->status);

        $this->post(route('printing-branding.schedule.store'), [
            'job_id' => $job->id,
            'machine_id' => $machine->id,
            'staff_id' => $this->admin->id,
            'starts_at' => '2026-08-24 09:00:00',
            'ends_at' => '2026-08-24 10:00:00',
        ])->assertRedirect();
        $this->assertInstanceOf(ProductionSchedule::class, ProductionSchedule::first());

        $this->post(route('printing-branding.quality'), [
            'job_id' => $job->id,
            'inspector_id' => $this->admin->id,
            'result' => 'Conditional Pass',
            'rejected_quantity' => 1,
            'reason' => 'Trim tolerance',
        ])->assertRedirect();
        $this->assertInstanceOf(QualityCheck::class, QualityCheck::first());

        $this->post(route('printing-branding.waste'), [
            'job_id' => $job->id,
            'material_id' => $material->id,
            'machine_id' => $machine->id,
            'waste_type' => 'Paper Waste',
            'quantity' => 2,
            'cost' => 100,
            'reason' => 'Test run',
        ])->assertRedirect();
        $this->assertInstanceOf(Waste::class, Waste::first());

        $this->post(route('printing-branding.outsourcing.store'), [
            'job_id' => $job->id,
            'service' => 'Special Lamination',
            'quantity' => 10,
            'cost' => 500,
            'expected_completion' => '2026-08-25',
        ])->assertRedirect();
        $this->assertInstanceOf(OutsourcingOrder::class, OutsourcingOrder::first());

        $this->post(route('printing-branding.dispatch.store'), [
            'job_id' => $job->id,
            'status' => 'Packed',
            'delivery_address' => 'Nairobi CBD',
            'dispatch_date' => '2026-08-25',
        ])->assertRedirect();
        $this->assertInstanceOf(Dispatch::class, Dispatch::first());

        $this->post(route('printing-branding.settings.templates.store'), [
            'template_code' => 'BC-300',
            'name' => 'Business Card 300gsm',
            'category' => 'Business Cards',
            'specifications' => ['Dimensions' => '90 x 50 mm'],
        ])->assertRedirect();
        $this->post(route('printing-branding.settings.print-methods.store'), [
            'method_code' => 'DIG',
            'name' => 'Digital Printing',
            'setup_cost' => 100,
        ])->assertRedirect();
        $this->post(route('printing-branding.settings.finishing.store'), [
            'option_code' => 'LAM',
            'name' => 'Gloss Lamination',
            'cost' => 50,
        ])->assertRedirect();
        $this->post(route('printing-branding.settings.pricing-rules.store'), [
            'rule_code' => 'MARKUP',
            'name' => 'Default Markup',
            'rule_type' => 'Material Markups',
            'rate' => 30,
        ])->assertRedirect();

        $this->assertInstanceOf(ProductTemplate::class, ProductTemplate::first());
        $this->assertInstanceOf(PrintMethod::class, PrintMethod::first());
        $this->assertInstanceOf(FinishingOption::class, FinishingOption::first());
        $this->assertInstanceOf(PricingRule::class, PricingRule::first());
    }

    public function test_printing_records_are_tenant_isolated_and_permissions_are_enforced(): void
    {
        ProductionJob::create([
            'client_id' => Client::create(['name' => 'Tenant Client'])->id,
            'job_number' => 'JOB-2026-90001',
            'product_name' => 'Tenant Job',
            'quantity' => 1,
            'status' => 'Draft',
        ]);
        $this->assertSame(1, ProductionJob::count());

        $otherTenant = PlatformTenant::create(['name' => 'Other Print', 'slug' => 'other-print', 'industry' => 'printing_branding', 'sub_industry' => 'digital-printing', 'status' => 'active']);
        $otherBusiness = Business::withoutGlobalScopes()->create(['tenant_id' => $otherTenant->id, 'name' => 'Other Print', 'slug' => 'other-print', 'industry' => 'printing_branding', 'is_active' => true]);
        ActiveTenant::switchTo($otherTenant);
        ActiveBusiness::switchTo($otherBusiness);
        $this->assertSame(0, ProductionJob::count());

        ActiveTenant::switchTo($this->tenant);
        ActiveBusiness::switchTo($this->business);

        $designer = User::factory()->create(['role' => 'staff', 'is_active' => true, 'status' => 'Active', 'current_tenant_id' => $this->tenant->id]);
        $role = IamRole::where('business_id', $this->business->id)->where('slug', 'graphic-designer')->firstOrFail();
        DB::table('business_user')->insert([
            'business_id' => $this->business->id,
            'user_id' => $designer->id,
            'iam_role_id' => $role->id,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($designer)->withSession([
            ActiveTenant::SESSION_KEY => $this->tenant->id,
            ActiveBusiness::SESSION_KEY => $this->business->id,
        ]);

        $this->post(route('printing-branding.estimates.store'), [
            'client_id' => Client::first()->id,
            'product_name' => 'Blocked Estimate',
            'quantity' => 1,
        ])->assertForbidden();
    }
}
