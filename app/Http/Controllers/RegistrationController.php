<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\IndustrySetupService;
use App\Services\PlanSelectionService;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    public function account()
    {
        return view('registration.account', [
            'account' => session('registration.account', []),
            'step' => 1,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        session(['registration.account' => $data]);

        return redirect()->route('register.company');
    }

    public function company(IndustrySetupService $industries)
    {
        return view('registration.company', [
            'company' => session('registration.company', []),
            'industries' => $industries->implementedIndustries(),
            'step' => 2,
        ]);
    }

    public function industryDashboard(Request $request, IndustrySetupService $industries)
    {
        $data = $request->validate([
            'industry' => ['required', Rule::in($industries->implementedSlugs())],
            'sub_industry' => ['nullable', 'string', 'max:80'],
        ]);

        if (! empty($data['sub_industry']) && ! in_array($data['sub_industry'], $industries->registrationSubIndustrySlugs($data['industry']), true)) {
            throw ValidationException::withMessages([
                'sub_industry' => 'Choose a valid sub-industry for the selected industry.',
            ]);
        }

        return response()->json($industries->dashboardFeatures($data['industry'], $data['sub_industry'] ?? null));
    }

    public function storeCompany(Request $request, IndustrySetupService $industries)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'industry' => ['required', Rule::in($industries->implementedSlugs())],
            'sub_industry' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'timezone'],
        ]);

        if (! in_array($data['sub_industry'], $industries->registrationSubIndustrySlugs($data['industry']), true)) {
            throw ValidationException::withMessages([
                'sub_industry' => 'Choose a valid sub-industry for the selected industry.',
            ]);
        }

        session(['registration.company' => $data]);

        return redirect()->route('register.plan');
    }

    public function plan(PlanSelectionService $plans)
    {
        $this->ensureRegistrationStep('registration.account', 'register.account');
        $this->ensureRegistrationStep('registration.company', 'register.company');

        return view('registration.plan', [
            'plans' => $plans->all(),
            'selectedPlan' => session('registration.plan', 'professional'),
            'step' => 3,
        ]);
    }

    public function storePlan(Request $request, PlanSelectionService $plans, TenantProvisioningService $provisioning)
    {
        $this->ensureRegistrationStep('registration.account', 'register.account');
        $this->ensureRegistrationStep('registration.company', 'register.company');

        $data = $request->validate([
            'plan' => ['required', Rule::in($plans->slugs())],
        ]);

        session(['registration.plan' => $data['plan']]);

        $result = $provisioning->provisionRegistration([
            'account' => session('registration.account'),
            'company' => session('registration.company'),
            'plan' => $data['plan'],
        ]);

        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->forget('registration');

        return redirect()->route('register.welcome')->with('status', 'Your workspace is ready. We sent an email verification link if mail is configured.');
    }

    public function welcome(IndustrySetupService $industries)
    {
        return view('registration.welcome', [
            'tenant' => auth()->user()?->currentTenant,
            'checklist' => $industries->onboardingChecklist(),
            'industryDashboard' => $industries->dashboardFeaturesForTenant(auth()->user()?->currentTenant),
        ]);
    }

    public function verificationNotice()
    {
        return view('auth.verify-email');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'The verification email could not be sent. Please check mail settings or contact support.']);
        }

        return back()->with('status', 'Verification link sent.');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals((string) $hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        if (Auth::id() !== $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('register.welcome')->with('status', 'Email verified successfully.');
    }

    private function ensureRegistrationStep(string $key, string $route): void
    {
        if (! session()->has($key)) {
            throw new HttpResponseException(redirect()->route($route));
        }
    }
}
