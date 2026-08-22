<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function create()
    {
        abort_unless(Schema::hasTable('tenants'), 404);

        return view('onboarding.tenant', [
            'plans' => Schema::hasTable('plans') ? Plan::where('is_active', true)->orderBy('monthly_price')->get() : collect(),
            'industries' => ['Construction', 'Healthcare', 'Education', 'Retail', 'Manufacturing', 'Hospitality', 'Logistics', 'RealEstate', 'ProfessionalServices'],
        ]);
    }

    public function store(Request $request, TenantProvisioningService $provisioning)
    {
        abort_unless(Schema::hasTable('tenants'), 404);

        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:100'],
            'plan' => ['nullable', Rule::exists('plans', 'slug')],
        ]);

        $tenant = $provisioning->provision($data, $request->user());

        return redirect()->route('dashboard')->with('status', $tenant->name.' tenant workspace created.');
    }
}
