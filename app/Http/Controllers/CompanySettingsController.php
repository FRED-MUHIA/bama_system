<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\Signatory;
use App\Models\TermsCondition;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompanySettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'settings' => CompanySetting::firstOrCreate(
                ['business_id' => ActiveBusiness::id()],
                ['company_name' => ActiveBusiness::current()?->name ?? 'BAMA']
            ),
            'methods' => Schema::hasTable('payment_methods') ? PaymentMethod::latest()->get() : collect(),
            'terms' => Schema::hasTable('terms_conditions') ? TermsCondition::latest()->get() : collect(),
            'users' => Schema::hasColumn('users', 'enable_otp_login') ? User::orderBy('name')->get() : collect(),
            'signatories' => Schema::hasTable('signatories') ? Signatory::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'tax_name' => ['nullable', 'string', 'max:50'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'locale' => ['required', 'string', 'max:20'],
            'default_terms' => ['nullable', 'string'],
        ]);

        $settings = CompanySetting::firstOrCreate(
            ['business_id' => ActiveBusiness::id()],
            ['company_name' => ActiveBusiness::current()?->name ?? 'BAMA']
        );
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);
        $data['tax_name'] = $data['tax_name'] ?? '';
        $data['tax_rate'] = $data['tax_rate'] ?? 0;
        $settings->update($data);

        return back()->with('status', 'Company settings updated.');
    }

    public function storePaymentMethod(Request $request)
    {
        abort_unless(Schema::hasTable('payment_methods'), 404);

        PaymentMethod::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'details' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Payment method saved.');
    }

    public function deletePaymentMethod(PaymentMethod $paymentMethod)
    {
        abort_unless(Schema::hasTable('payment_methods'), 404);

        $paymentMethod->delete();
        return back()->with('status', 'Payment method removed.');
    }

    public function storeTerms(Request $request)
    {
        abort_unless(Schema::hasTable('terms_conditions'), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        if ($request->boolean('is_default')) {
            TermsCondition::query()->update(['is_default' => false]);
        }
        TermsCondition::create($data + ['is_default' => $request->boolean('is_default')]);

        return back()->with('status', 'Terms saved.');
    }

    public function deleteTerms(TermsCondition $termsCondition)
    {
        abort_unless(Schema::hasTable('terms_conditions'), 404);

        $termsCondition->delete();
        return back()->with('status', 'Terms removed.');
    }

    public function storeSignatory(Request $request)
    {
        abort_unless(Schema::hasTable('signatories'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'image', 'max:2048'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            Signatory::where('is_default', true)->update(['is_default' => false]);
        }

        $signatory = Signatory::create([
            'name' => $data['name'],
            'title' => $data['title'] ?? null,
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
        ]);

        if ($request->hasFile('signature')) {
            $signatory->update(['signature_path' => $request->file('signature')->store('signatures', 'public')]);
        }

        return back()->with('status', 'Signatory saved.');
    }

    public function deleteSignatory(Signatory $signatory)
    {
        abort_unless(Schema::hasTable('signatories'), 404);

        $signatory->delete();
        return back()->with('status', 'Signatory removed.');
    }
}
