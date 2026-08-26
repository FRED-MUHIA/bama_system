<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\IndustrySetupService;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function create(IndustrySetupService $industries)
    {
        abort_unless(Schema::hasTable('tenants'), 404);

        return view('onboarding.tenant', [
            'plans' => Schema::hasTable('plans') ? Plan::where('is_active', true)->orderBy('monthly_price')->get() : collect(),
            'industries' => $industries->implementedIndustries(),
        ]);
    }

    public function store(Request $request, TenantProvisioningService $provisioning, IndustrySetupService $industries)
    {
        abort_unless(Schema::hasTable('tenants'), 404);

        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['required', Rule::in($industries->implementedSlugs())],
            'sub_industry' => ['nullable', 'string', 'max:80'],
            'plan' => ['nullable', Rule::exists('plans', 'slug')],
        ]);

        $validSubIndustries = $industries->registrationSubIndustrySlugs($data['industry']);
        $data['sub_industry'] = $data['sub_industry'] ?: ($validSubIndustries[0] ?? null);

        if ($data['sub_industry'] && ! in_array($data['sub_industry'], $validSubIndustries, true)) {
            return back()
                ->withErrors(['sub_industry' => 'Choose a valid sub-industry for the selected industry.'])
                ->withInput();
        }

        $tenant = $provisioning->provision($data, $request->user());

        return redirect()->route('dashboard')->with('status', $tenant->name.' tenant workspace created.');
    }
}
