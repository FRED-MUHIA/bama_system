<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriptionAdjustmentController extends Controller
{
    public function update(Request $request, Tenant $tenant)
    {
        abort_unless($request->user()?->role === 'super_admin', 403);
        $data = $request->validate([
            'action' => ['required', Rule::in(['reset_monthly', 'set_expiry'])],
            'expires_at' => ['nullable', 'required_if:action,set_expiry', 'date', 'after:now'],
            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'reason' => ['required', 'string', 'max:1000', 'regex:/\S/'],
        ]);

        DB::transaction(function () use ($request, $tenant, $data) {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $subscription = Subscription::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->latest('id')->lockForUpdate()->first() ?? new Subscription(['tenant_id' => $tenant->id]);
            $expiry = match ($data['action']) {
                'reset_monthly' => now()->addDays(30),
                default => Carbon::parse($data['expires_at']),
            };
            $before = $subscription->only(['plan_id', 'status', 'starts_at', 'trial_ends_at', 'renews_at', 'grace_ends_at', 'ends_at', 'locked_at']);
            $before['tenant_status'] = $tenant->status;
            $metadata = $subscription->metadata ?? [];
            $metadata['admin_access_expires_at'] = $expiry->toDateTimeString();
            $subscription->forceFill([
                'plan_id' => $data['plan_id'], 'status' => 'active',
                'starts_at' => $subscription->starts_at ?: now(),
                'trial_ends_at' => $subscription->trial_ends_at,
                'renews_at' => $expiry,
                'grace_ends_at' => null, 'ends_at' => null, 'locked_at' => null,
                'last_renewal_notice_sent_at' => null, 'last_grace_notice_sent_at' => null,
            ]);
            $after = $subscription->only(array_keys($before));
            $after['tenant_status'] = 'active';
            $metadata['admin_adjustments'][] = [
                'at' => now()->toIso8601String(), 'user_id' => $request->user()->id,
                'user_name' => $request->user()->name, 'action' => $data['action'],
                'reason' => trim($data['reason']), 'before' => $before,
                'after' => $after,
                'expires_at' => $expiry->toDateTimeString(),
            ];
            $subscription->forceFill(['metadata' => $metadata])->save();
            $tenant->forceFill([
                'status' => 'active',
                'trial_ends_at' => $subscription->trial_ends_at,
            ])->save();
        });

        return redirect()->route('platform.tenants')->with('status', 'Subscription adjusted and workspace access restored.');
    }
}
