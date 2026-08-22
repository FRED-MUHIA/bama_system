<?php

namespace Modules\Fitness\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;
use Modules\Fitness\Services\FitnessFeatureGate;
use Modules\Fitness\Services\FitnessQrCodeService;

class MembershipPlanController extends Controller
{
    public function index(FitnessFeatureGate $gate, FitnessQrCodeService $qrCodes)
    {
        $gate->authorize('memberships');

        $issuedMemberships = MemberMembership::with('member.client', 'member.assignedTrainer', 'plan', 'invoice')->latest()->limit(24)->get();

        return view('fitness.memberships', [
            'section' => 'plans',
            'plans' => MembershipPlan::latest()->paginate(20),
            'issuedMemberships' => $issuedMemberships,
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'membershipQrCodes' => $issuedMemberships
                ->filter(fn (MemberMembership $membership) => (bool) $membership->member?->qr_code)
                ->mapWithKeys(fn (MemberMembership $membership) => [$membership->id => $qrCodes->dataUri($membership->member->qr_code, 96)])
                ->all(),
            'planTypes' => MembershipPlan::TYPES,
            'statuses' => MembershipPlan::STATUSES,
        ]);
    }

    public function store(Request $request, FitnessFeatureGate $gate)
    {
        $gate->authorize('memberships');

        $data = $this->validated($request);
        MembershipPlan::create($data);

        return back()->with('status', 'Membership plan created.');
    }

    public function update(Request $request, MembershipPlan $membershipPlan, FitnessFeatureGate $gate)
    {
        $gate->authorize('memberships');

        $membershipPlan->update($this->validated($request, $membershipPlan));

        return back()->with('status', 'Membership plan updated.');
    }

    private function validated(Request $request, ?MembershipPlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('fitness_membership_plans', 'name')->where('business_id', \App\Support\ActiveBusiness::id())->ignore($plan)],
            'code' => ['nullable', 'string', 'max:50'],
            'plan_type' => ['required', Rule::in(MembershipPlan::TYPES)],
            'description' => ['nullable', 'string'],
            'currency' => ['required', 'size:3'],
            'price' => ['required', 'numeric', 'min:0'],
            'joining_fee' => ['nullable', 'numeric', 'min:0'],
            'renewal_fee' => ['nullable', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'session_credits' => ['nullable', 'integer', 'min:0'],
            'freeze_allowed' => ['nullable', 'boolean'],
            'guest_passes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(MembershipPlan::STATUSES)],
        ]);

        foreach ([
            'joining_fee' => 0,
            'renewal_fee' => 0,
            'freeze_allowed' => false,
            'guest_passes' => 0,
        ] as $field => $default) {
            $data[$field] ??= $default;
        }

        return $data;
    }
}
