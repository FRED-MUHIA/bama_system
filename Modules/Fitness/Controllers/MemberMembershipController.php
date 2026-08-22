<?php

namespace Modules\Fitness\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Fitness\Models\Member;
use Modules\Fitness\Models\MemberMembership;
use Modules\Fitness\Models\MembershipPlan;
use Modules\Fitness\Services\FitnessBillingService;
use Modules\Fitness\Services\FitnessFeatureGate;
use Modules\Fitness\Services\MembershipService;

class MemberMembershipController extends Controller
{
    public function store(Request $request, MembershipService $memberships, FitnessFeatureGate $gate)
    {
        $gate->authorize('memberships');

        $data = $request->validate([
            'fitness_member_id' => ['required', Rule::exists('fitness_members', 'id')->where('business_id', ActiveBusiness::id())],
            'fitness_membership_plan_id' => ['required', Rule::exists('fitness_membership_plans', 'id')->where('business_id', ActiveBusiness::id())],
            'starts_at' => ['required', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
            'price_charged' => ['nullable', 'numeric', 'min:0'],
            'joining_fee_charged' => ['nullable', 'numeric', 'min:0'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(MemberMembership::STATUSES)],
        ]);

        $membership = $memberships->enroll(
            Member::findOrFail($data['fitness_member_id']),
            MembershipPlan::findOrFail($data['fitness_membership_plan_id']),
            $data
        );

        return back()->with('status', 'Membership '.$membership->membership_number.' enrolled.');
    }

    public function renew(Request $request, MemberMembership $memberMembership, MembershipService $memberships, FitnessFeatureGate $gate)
    {
        $gate->authorize('memberships');

        $data = $request->validate([
            'fitness_membership_plan_id' => ['nullable', Rule::exists('fitness_membership_plans', 'id')->where('business_id', ActiveBusiness::id())],
            'price_charged' => ['nullable', 'numeric', 'min:0'],
        ]);

        $plan = ! empty($data['fitness_membership_plan_id']) ? MembershipPlan::findOrFail($data['fitness_membership_plan_id']) : null;
        $membership = $memberships->renew($memberMembership, $plan, $data);

        return back()->with('status', 'Membership '.$membership->membership_number.' renewed.');
    }

    public function freeze(Request $request, MemberMembership $memberMembership, MembershipService $memberships, FitnessFeatureGate $gate)
    {
        $gate->authorize('memberships');

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $memberships->freeze($memberMembership, $data);

        return back()->with('status', 'Membership frozen.');
    }

    public function invoice(MemberMembership $memberMembership, FitnessBillingService $billing, FitnessFeatureGate $gate)
    {
        $gate->authorize('payments');

        $invoice = $billing->invoiceMembership($memberMembership);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Membership invoice generated.');
    }

    public function payment(Request $request, MemberMembership $memberMembership, FitnessBillingService $billing, FitnessFeatureGate $gate)
    {
        $gate->authorize('payments');

        $data = $request->validate([
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', ActiveBusiness::id())],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $receipt = $billing->recordMembershipPayment($memberMembership, $data);

        return redirect()->route('receipts.show', $receipt)->with('status', 'Membership payment recorded.');
    }

    public function recordAndEmailPayment(Request $request, MemberMembership $memberMembership, FitnessBillingService $billing, FitnessFeatureGate $gate)
    {
        $gate->authorize('payments');

        $data = $request->validate([
            'payment_method_id' => ['nullable', Rule::exists('payment_methods', 'id')->where('business_id', ActiveBusiness::id())],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $receipt = $billing->recordMembershipPayment($memberMembership, $data + [
            'notes' => $data['notes'] ?? 'Fitness membership payment recorded',
        ]);
        $sent = $billing->sendInvoiceAfterPayment($receipt->invoice, $receipt);

        return redirect()
            ->route('invoices.show', $receipt->invoice)
            ->with('status', 'Payment recorded. Invoice '.$receipt->invoice->invoice_number.' generated for the client'.($sent ? ' and emailed.' : '. Email was not sent; check the invoice email history.'));
    }
}
