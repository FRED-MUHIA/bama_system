<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MembershipPlan;
use Modules\Fitness\Services\MembershipService;
use Tests\TestCase;

class FitnessOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;
    private ?Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::where('slug', 'bama')->firstOrFail();
        $this->tenant = Tenant::first();
        $this->user = User::factory()->create(['role' => 'admin', 'is_active' => true, 'status' => 'Active']);
        $this->actingAs($this->user)->withSession(['active_business_id' => $this->business->id, 'active_tenant_id' => $this->tenant?->id]);

        $moduleId = DB::table('modules')->where('slug', 'fitness')->value('id');
        if ($moduleId && $this->tenant) {
            DB::table('tenant_modules')->updateOrInsert(
                ['tenant_id' => $this->tenant->id, 'module_id' => $moduleId],
                ['enabled' => true, 'enabled_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function test_check_in_decrements_session_credit_and_check_out_closes_visit(): void
    {
        $member = $this->activeMember('Check In Member', 2);

        $this->post(route('fitness.check-in.store'), [
            'member_identifier' => $member->member_number,
            'method' => 'Manual',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fitness_attendance_logs', [
            'business_id' => $this->business->id,
            'fitness_member_id' => $member->id,
            'status' => 'In Gym',
        ]);

        $this->assertSame(1, (int) DB::table('fitness_member_memberships')->where('fitness_member_id', $member->id)->value('session_credits_remaining'));

        $this->post(route('fitness.check-out.store'), [
            'member_identifier' => $member->member_number,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fitness_attendance_logs', [
            'business_id' => $this->business->id,
            'fitness_member_id' => $member->id,
            'status' => 'Checked Out',
        ]);
        $this->assertNotNull(DB::table('fitness_attendance_logs')->where('fitness_member_id', $member->id)->value('exit_time'));
    }

    public function test_check_in_accepts_membership_id(): void
    {
        $member = $this->activeMember('Membership Id Check In', 2)->load('activeMembership');

        $this->post(route('fitness.check-in.store'), [
            'member_identifier' => $member->activeMembership->membership_number,
            'method' => 'Membership Card',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fitness_attendance_logs', [
            'business_id' => $this->business->id,
            'fitness_member_id' => $member->id,
            'method' => 'Membership Card',
            'status' => 'In Gym',
        ]);
    }

    public function test_class_booking_enforces_capacity_and_waitlist(): void
    {
        $first = $this->activeMember('First Booking Member');
        $second = $this->activeMember('Second Booking Member');

        $classId = DB::table('fitness_classes')->insertGetId([
            'business_id' => $this->business->id,
            'name' => 'Morning HIIT',
            'capacity' => 1,
            'duration_minutes' => 45,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionId = DB::table('fitness_class_sessions')->insertGetId([
            'business_id' => $this->business->id,
            'fitness_class_id' => $classId,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(45),
            'capacity' => 1,
            'status' => 'Scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('fitness.class-bookings.store'), [
            'fitness_class_session_id' => $sessionId,
            'fitness_member_id' => $first->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('fitness.class-bookings.store'), [
            'fitness_class_session_id' => $sessionId,
            'fitness_member_id' => $second->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fitness_class_bookings', [
            'business_id' => $this->business->id,
            'fitness_class_session_id' => $sessionId,
            'fitness_member_id' => $first->id,
            'status' => 'Booked',
        ]);
        $this->assertDatabaseHas('fitness_class_bookings', [
            'business_id' => $this->business->id,
            'fitness_class_session_id' => $sessionId,
            'fitness_member_id' => $second->id,
            'status' => 'Waitlisted',
        ]);
    }

    public function test_operation_tabs_render(): void
    {
        foreach ([
            'fitness.trainers.index',
            'fitness.attendance.index',
            'fitness.check-in.index',
            'fitness.classes.index',
            'fitness.programs.index',
            'fitness.exercises.index',
            'fitness.health-profiles.index',
            'fitness.assessments.index',
            'fitness.personal-training.index',
            'fitness.nutrition.index',
            'fitness.challenges.index',
            'fitness.equipment.index',
            'fitness.reports.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_member_membership_card_shows_id_and_qr_code(): void
    {
        $member = $this->activeMember('Card Member', 5)->load('activeMembership');

        $this->get(route('fitness.members.card', $member))
            ->assertOk()
            ->assertSee('Fitness Membership Card')
            ->assertSee($member->member_number)
            ->assertSee($member->qr_code)
            ->assertSee($member->activeMembership->membership_number)
            ->assertSee('data:image/svg+xml', false);
    }

    public function test_memberships_page_shows_issued_ids_and_qr_codes(): void
    {
        $member = $this->activeMember('Visible Id Member', 5)->load('activeMembership');

        $this->get(route('fitness.memberships.index'))
            ->assertOk()
            ->assertSee('Issued Membership Cards / IDs')
            ->assertSee('Payment recording')
            ->assertSee('Record + Email Invoice')
            ->assertSee($member->activeMembership->membership_number)
            ->assertSee($member->member_number)
            ->assertSee($member->qr_code)
            ->assertSee('data:image/svg+xml', false);
    }

    public function test_members_page_shows_member_history(): void
    {
        $member = $this->activeMember('History Member', 5)->load('activeMembership');

        $this->post(route('fitness.check-in.store'), [
            'member_identifier' => $member->member_number,
            'method' => 'Manual',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->get(route('fitness.members.index'))
            ->assertOk()
            ->assertSee('Member History')
            ->assertSee('Membership Timeline')
            ->assertSee('Payments & Invoices', false)
            ->assertSee('Attendance')
            ->assertSee($member->activeMembership->membership_number)
            ->assertSee('1 visits');
    }

    public function test_record_payment_generates_and_emails_invoice(): void
    {
        Mail::fake();
        $member = $this->activeMember('Recorded Payment Member', 5)->load('activeMembership', 'client');
        $member->client()->update(['email' => 'recorded-payment@example.test']);
        $membership = $member->activeMembership;

        $response = $this->post(route('fitness.member-memberships.record-payment', $membership), [
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'reference' => 'RECEIVED-001',
        ]);

        $membership->refresh();
        $invoice = $membership->invoice;

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('status', 'Payment recorded. Invoice '.$invoice->invoice_number.' generated for the client and emailed.');
        $this->assertNotNull($invoice);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'client_id' => $member->client_id,
            'amount_paid' => 1000,
            'payment_status' => 'partial',
        ]);
        $this->assertNotNull($invoice->fresh()->sent_at);
        $this->assertDatabaseHas('email_logs', [
            'emailable_type' => $invoice->getMorphClass(),
            'emailable_id' => $invoice->id,
            'recipient_email' => 'recorded-payment@example.test',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'payable_id' => $membership->id,
            'amount' => 1000,
            'reference' => 'RECEIVED-001',
        ]);
        $this->assertDatabaseHas('receipts', [
            'invoice_id' => $invoice->id,
            'amount_paid' => 1000,
        ]);
    }

    public function test_check_in_page_shows_eligible_membership_identifiers(): void
    {
        $member = $this->activeMember('Eligible Check In Member', 5)->load('activeMembership');

        $this->get(route('fitness.check-in.index'))
            ->assertOk()
            ->assertSee('Eligible For Check-In')
            ->assertSee($member->activeMembership->membership_number)
            ->assertSee($member->member_number)
            ->assertSee($member->qr_code);
    }

    public function test_nutrition_assignment_can_be_downloaded_as_pdf(): void
    {
        $member = $this->activeMember('Nutrition Download Member', 5);

        $planId = DB::table('fitness_nutrition_plans')->insertGetId([
            'business_id' => $this->business->id,
            'name' => 'Strength Meal Plan',
            'calories' => 2400,
            'protein' => 160,
            'carbohydrates' => 260,
            'fat' => 70,
            'fiber' => 35,
            'water_intake_goal' => 3000,
            'meals' => json_encode(['notes' => "Breakfast: oats\nLunch: chicken and rice\nDinner: fish and vegetables"]),
            'status' => 'Active',
            'description' => 'High protein plan for strength training.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('fitness_nutrition_assignments')->insertGetId([
            'business_id' => $this->business->id,
            'fitness_nutrition_plan_id' => $planId,
            'fitness_member_id' => $member->id,
            'starts_at' => now()->toDateString(),
            'compliance_percent' => 75,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('fitness.nutrition-assignments.download', $assignmentId))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
    }

    private function activeMember(string $name, ?int $credits = null): Member
    {
        $plan = MembershipPlan::create([
            'name' => $name.' Plan',
            'plan_type' => 'Monthly',
            'currency' => 'KES',
            'price' => 3000,
            'duration_days' => 30,
            'session_credits' => $credits,
            'status' => 'Active',
        ]);

        $member = app(MembershipService::class)->createMember(['name' => $name], ['status' => 'Pending']);
        app(MembershipService::class)->enroll($member, $plan, ['starts_at' => now()->toDateString()]);

        return $member->fresh();
    }
}
