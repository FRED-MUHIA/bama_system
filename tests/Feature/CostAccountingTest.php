<?php

namespace Tests\Feature;

use App\Models\AccountingAllocation;
use App\Models\AccountingBudget;
use App\Models\Business;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Services\CostAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostAccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::where('slug', 'bama')->firstOrFail();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin)->withSession(['active_business_id' => $this->business->id]);
    }

    public function test_industry_defaults_are_additive_and_business_scoped(): void
    {
        $this->post(route('accounting.industry.store'), ['industry' => 'Technology'])->assertRedirect();
        $this->assertDatabaseHas('departments', ['business_id' => $this->business->id, 'name' => 'ICT Services']);
        $this->post(route('accounting.industry.store'), ['industry' => 'Technology'])->assertRedirect();
        $this->assertSame(5, Department::count());
    }

    public function test_new_project_automatically_becomes_a_cost_center(): void
    {
        $clientId = \DB::table('clients')->insertGetId(['business_id' => $this->business->id, 'name' => 'Acme', 'email' => 'a@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $project = Project::create(['client_id' => $clientId, 'project_name' => 'SARIN', 'status' => 'Lead']);
        $project->refresh();
        $this->assertNotNull($project->cost_center_id);
        $this->assertDatabaseHas('cost_centers', ['id' => $project->cost_center_id, 'project_id' => $project->id, 'is_project' => true]);
    }

    public function test_allocations_require_a_valid_accounting_tag_and_inherit_project_hierarchy(): void
    {
        $clientId = \DB::table('clients')->insertGetId(['business_id' => $this->business->id, 'name' => 'Acme', 'email' => 'a@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $project = Project::create(['client_id' => $clientId, 'project_name' => 'TULSI', 'status' => 'Lead']);
        $this->post(route('accounting.allocations.store'), ['transaction_type' => 'Expense', 'direction' => 'Expense', 'transaction_date' => '2026-07-13', 'amount' => 20000])->assertSessionHasErrors('department_id');
        $this->post(route('accounting.allocations.store'), ['transaction_type' => 'Expense', 'project_id' => $project->id, 'direction' => 'Expense', 'transaction_date' => '2026-07-13', 'amount' => 20000])->assertSessionHasNoErrors();
        $allocation = AccountingAllocation::firstOrFail();
        $this->assertSame($project->cost_center_id, $allocation->cost_center_id);
        $this->assertNotNull($allocation->department_id);
    }

    public function test_profitability_and_budget_variance_use_existing_project_records(): void
    {
        $clientId = \DB::table('clients')->insertGetId(['business_id' => $this->business->id, 'name' => 'Acme', 'email' => 'a@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $project = Project::create(['client_id' => $clientId, 'project_name' => 'SARIN', 'status' => 'Lead']);
        \DB::table('invoices')->insert(['business_id' => $this->business->id, 'client_id' => $clientId, 'project_id' => $project->id, 'invoice_number' => 'INV-1', 'invoice_date' => '2026-01-01', 'payment_status' => 'Unpaid', 'subtotal' => 397416, 'discount_total' => 0, 'tax_total' => 0, 'total' => 397416, 'amount_paid' => 0, 'balance' => 397416, 'created_at' => now(), 'updated_at' => now()]);
        $project->expenses()->create(['expense_date' => '2026-01-02', 'category' => 'Installation', 'amount' => 230000]);
        AccountingBudget::create(['name' => 'SARIN Budget', 'fiscal_year' => 2026, 'amount' => 250000, 'project_id' => $project->id, 'status' => 'Approved']);
        $report = app(CostAccountingService::class)->report(2026);
        $row = $report['projectRows']->firstWhere('project.id', $project->id);
        $this->assertEquals(167416, $row['profit']);
        $this->assertEqualsWithDelta(42.126, $row['margin'], 0.01);
        $this->assertEquals(20000, $report['budgets']->first()['variance']);
    }

    public function test_accounting_dashboard_renders_reports_and_controls(): void
    {
        $this->get(route('accounting.index'))->assertOk()->assertSee('Department P&amp;L', false)->assertSee('Project profitability')->assertSee('Tag transaction');
    }

    public function test_budget_threshold_creates_persistent_alert(): void
    {
        $clientId = \DB::table('clients')->insertGetId(['business_id' => $this->business->id, 'name' => 'Alert Client', 'created_at' => now(), 'updated_at' => now()]);
        $project = Project::create(['client_id' => $clientId, 'project_name' => 'Alert Project', 'status' => 'Lead']);
        $project->expenses()->create(['expense_date' => now(), 'category' => 'Work', 'amount' => 90]);
        AccountingBudget::create(['name' => 'Alert Budget', 'fiscal_year' => now()->year, 'amount' => 100, 'alert_threshold' => 80, 'project_id' => $project->id]);
        app(CostAccountingService::class)->report(now()->year);
        $this->assertDatabaseHas('budget_alerts', ['severity' => 'Threshold', 'actual_amount' => 90]);
    }

    public function test_project_reports_export_and_erp_audit_coverage_work(): void
    {
        $clientId = \DB::table('clients')->insertGetId(['business_id' => $this->business->id, 'name' => 'Report Client', 'created_at' => now(), 'updated_at' => now()]);
        $project = Project::create(['client_id' => $clientId, 'project_name' => 'Report Project', 'status' => 'Lead']);
        $this->assertDatabaseHas('accounting_audit_logs', ['auditable_type' => Project::class, 'auditable_id' => $project->id, 'event' => 'created']);
        $this->get(route('accounting.reports.show', ['type' => 'profit-loss', 'project_id' => $project->id, 'csv' => 1]))->assertOk()->assertDownload('Report Project-profit-loss.csv');
    }

    public function test_edit_controls_and_embedded_invoice_tags_are_visible(): void
    {
        $this->get(route('accounting.index'))->assertOk()->assertSee('Update department')->assertSee('Update cost center')->assertSee('Update budget');
        $this->get(route('invoices.create'))->assertOk()->assertSee('Cost center')->assertSee('Department');
    }
}
