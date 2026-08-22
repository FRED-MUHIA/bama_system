<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;
use Modules\Fitness\Services\MembershipService;

class FitnessMembershipTest extends TestCase
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

    public function test_member_can_be_enrolled_renewed_and_frozen(): void
    {
        $this->travelTo('2026-07-15 10:00:00');

        $plan = MembershipPlan::create([
            'name' => 'Premium VIP',
            'plan_type' => 'Premium VIP',
            'currency' => 'KES',
            'price' => 10000,
            'joining_fee' => 1000,
            'renewal_fee' => 9000,
            'duration_days' => 30,
            'session_credits' => 12,
            'freeze_allowed' => true,
            'guest_passes' => 2,
            'status' => 'Active',
        ]);

        $service = app(MembershipService::class);
        $member = $service->createMember(
            ['name' => 'Fitness Member', 'email' => 'member@example.test'],
            ['status' => 'Pending']
        );
        $membership = $service->enroll($member, $plan, ['starts_at' => '2026-07-01']);

        $this->assertSame('Active', $membership->status);
        $this->assertSame(12, $membership->session_credits_remaining);
        $this->assertSame('2026-07-30', $membership->ends_at->toDateString());
        $this->assertSame('Active', $member->fresh()->status);

        $renewed = $service->renew($membership);
        $this->assertSame('2026-08-29', $renewed->ends_at->toDateString());
        $this->assertSame('Active', $renewed->status);

        $frozen = $service->freeze($renewed, [
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-08-05',
            'reason' => 'Medical rest',
        ]);

        $this->assertSame('Frozen', $frozen->status);
        $this->assertSame('2026-09-03', $frozen->ends_at->toDateString());
        $this->assertCount(1, $frozen->freezes);
    }

    public function test_blank_optional_plan_fields_default_to_zero_when_created_from_form(): void
    {
        $response = $this->from(route('fitness.memberships.index'))->post(route('fitness.membership-plans.store'), [
            'name' => 'Blank Optional Fees',
            'plan_type' => 'Monthly',
            'description' => '',
            'currency' => 'KES',
            'price' => 3000,
            'joining_fee' => '',
            'renewal_fee' => '',
            'duration_days' => 20,
            'session_credits' => '',
            'guest_passes' => '',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('fitness.memberships.index'));
        $response->assertSessionHasNoErrors();

        $plan = MembershipPlan::where('name', 'Blank Optional Fees')->firstOrFail();

        $this->assertSame('0.00', $plan->joining_fee);
        $this->assertSame('0.00', $plan->renewal_fee);
        $this->assertSame(0, $plan->guest_passes);
        $this->assertFalse($plan->freeze_allowed);
        $this->assertNull($plan->session_credits);
    }

    public function test_expiry_command_expires_due_memberships(): void
    {
        $plan = MembershipPlan::create([
            'name' => 'Monthly',
            'plan_type' => 'Monthly',
            'currency' => 'KES',
            'price' => 3000,
            'duration_days' => 30,
            'status' => 'Active',
        ]);
        $member = app(MembershipService::class)->createMember(['name' => 'Expired Member'], ['status' => 'Pending']);
        $membership = app(MembershipService::class)->enroll($member, $plan, ['starts_at' => now()->subDays(45)->toDateString()]);

        $this->artisan('fitness:expire-memberships')->assertSuccessful();

        $this->assertSame('Expired', $membership->fresh()->status);
        $this->assertSame('Expired', $member->fresh()->status);
    }

    public function test_members_are_isolated_by_active_business(): void
    {
        $otherBusiness = Business::create([
            'tenant_id' => Tenant::first()?->id,
            'name' => 'Other Gym',
            'slug' => 'other-gym',
            'is_active' => true,
        ]);

        Member::create([
            'business_id' => $this->business->id,
            'client_id' => Client::create(['name' => 'Visible Member'])->id,
            'member_number' => 'MEM-A',
            'qr_code' => 'QR-A',
            'status' => 'Active',
        ]);

        Member::create([
            'business_id' => $otherBusiness->id,
            'client_id' => Client::withoutGlobalScopes()->create(['business_id' => $otherBusiness->id, 'name' => 'Hidden Member'])->id,
            'member_number' => 'MEM-B',
            'qr_code' => 'QR-B',
            'status' => 'Active',
        ]);

        $this->assertSame(1, Member::count());
        $this->assertSame('Visible Member', Member::first()->client->name);

        $this->withSession(['active_business_id' => $otherBusiness->id]);
        $this->assertSame(1, Member::count());
        $this->assertSame('Hidden Member', Member::first()->client->name);
    }
}
