<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    public function index()
    {
        return view('platform.index', [
            'tenants' => Tenant::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->with(['subscription' => fn ($query) => $query->withoutGlobalScopes()->with('plan')])
                ->withCount([
                    'businesses' => fn ($query) => $query->withoutGlobalScopes(),
                    'users',
                ])
                ->latest()
                ->limit(8)
                ->get(),
            'plans' => Plan::withCount('features')->orderBy('monthly_price')->get(),
            'metrics' => [
                'tenants' => Tenant::withoutGlobalScopes()->whereNull('deleted_at')->count(),
                'businesses' => Business::withoutGlobalScopes()
                    ->whereHas('tenant', fn ($query) => $query->whereNull('deleted_at'))
                    ->count(),
                'users' => User::count(),
                'activeSubscriptions' => Subscription::withoutGlobalScopes()->whereIn('status', ['active', 'trialing'])->count(),
            ],
        ]);
    }

    public function tenants()
    {
        return view('platform.tenants', [
            'tenants' => Tenant::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->with([
                    'businesses' => fn ($query) => $query->withoutGlobalScopes(),
                    'subscription' => fn ($query) => $query->withoutGlobalScopes()->with('plan'),
                ])
                ->withCount('users')
                ->latest()
                ->paginate(20),
            'plans' => Plan::where('is_active', true)->orderBy('monthly_price')->get(),
            'statuses' => ['trial', 'active', 'suspended', 'cancelled'],
            'subscriptionStatuses' => ['trialing', 'active', 'past_due', 'paused', 'cancelled'],
        ]);
    }

    public function updateTenant(Request $request, Tenant $tenant)
    {
        $subscription = Subscription::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();

        $data = $request->validate([
            'status' => ['required', Rule::in(['trial', 'active', 'suspended', 'cancelled'])],
            'primary_domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'primary_domain')->ignore($tenant)],
            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'subscription_status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'paused', 'cancelled'])],
            'trial_ends_at' => ['nullable', 'date'],
            'renews_at' => ['nullable', 'date'],
        ]);

        $tenant->update([
            'status' => $data['status'],
            'primary_domain' => $data['primary_domain'] ?: null,
            'trial_ends_at' => $data['trial_ends_at'] ?? null,
        ]);

        Subscription::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $data['plan_id'],
                'status' => $data['subscription_status'],
                'starts_at' => $subscription?->starts_at ?? now(),
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'renews_at' => $data['renews_at'] ?? null,
                'ends_at' => $data['subscription_status'] === 'cancelled' ? now() : null,
            ]
        );

        return back()->with('status', 'Tenant updated.');
    }

    public function destroyTenant(Request $request, Tenant $tenant)
    {
        $request->validate([
            'confirm_delete' => ['accepted'],
        ]);

        abort_if(
            $tenant->users()->where('users.role', 'super_admin')->exists(),
            422,
            'The owner management profile cannot be deleted from client management.'
        );

        DB::transaction(function () use ($tenant) {
            $businessIds = Business::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->pluck('id');

            $userIds = DB::table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->pluck('user_id');

            if ($businessIds->isNotEmpty()) {
                DB::table('business_user')->whereIn('business_id', $businessIds)->delete();

                Business::withoutGlobalScopes()
                    ->whereIn('id', $businessIds)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }

            if ($userIds->isNotEmpty()) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                User::whereIn('id', $userIds)
                    ->where('current_tenant_id', $tenant->id)
                    ->update(['current_tenant_id' => null]);
            }

            DB::table('tenant_user')->where('tenant_id', $tenant->id)->delete();

            Subscription::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['cancelled', 'paused'])
                ->update([
                    'status' => 'cancelled',
                    'ends_at' => now(),
                    'updated_at' => now(),
                ]);

            $tenant->update(['status' => 'cancelled']);
            $tenant->delete();
        });

        return redirect()->route('platform.tenants')->with('status', "Profile {$tenant->name} deleted.");
    }

    public function plans()
    {
        return view('platform.plans', [
            'plans' => Plan::with('features')->orderBy('monthly_price')->get(),
        ]);
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'limits.users' => ['nullable', 'integer', 'min:0'],
            'limits.storage_mb' => ['nullable', 'integer', 'min:0'],
            'limits.branches' => ['nullable', 'integer', 'min:0'],
            'limits.projects' => ['nullable', 'integer', 'min:0'],
            'limits.api_access' => ['nullable', 'boolean'],
        ]);

        $limits = [
            'users' => $data['limits']['users'] ?? null,
            'storage_mb' => $data['limits']['storage_mb'] ?? null,
            'branches' => $data['limits']['branches'] ?? null,
            'projects' => $data['limits']['projects'] ?? null,
            'api_access' => (bool) ($data['limits']['api_access'] ?? false),
        ];

        $plan->update([
            'name' => $data['name'],
            'monthly_price' => $data['monthly_price'],
            'currency' => strtoupper($data['currency']),
            'limits' => $limits,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        foreach ($limits as $feature => $limit) {
            SubscriptionFeature::updateOrCreate(
                ['plan_id' => $plan->id, 'feature' => $feature],
                [
                    'limit' => is_bool($limit) || is_null($limit) ? null : $limit,
                    'value' => is_bool($limit) ? ($limit ? 'true' : 'false') : null,
                    'enabled' => $limit !== false,
                ]
            );
        }

        return back()->with('status', 'Plan updated.');
    }
}
