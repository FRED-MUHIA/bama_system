<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\Signatory;
use App\Models\TermsCondition;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanySettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'settings' => $this->activeCompanySettings(),
            'methods' => Schema::hasTable('payment_methods') ? PaymentMethod::latest()->get() : collect(),
            'terms' => Schema::hasTable('terms_conditions') ? TermsCondition::latest()->get() : collect(),
            'users' => $this->profileUsers(),
            'signatories' => Schema::hasTable('signatories') ? Signatory::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'tax_name' => ['nullable', 'string', 'max:50'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'locale' => ['required', 'string', 'max:20'],
            'default_terms' => ['nullable', 'string'],
        ]);

        $settings = $this->activeCompanySettings();
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }
        unset($data['logo']);
        $data['primary_color'] = $data['primary_color'] ?? CompanySetting::DEFAULT_PRIMARY_COLOR;
        $data['secondary_color'] = $data['secondary_color'] ?? CompanySetting::DEFAULT_SECONDARY_COLOR;
        $data['accent_color'] = $data['accent_color'] ?? CompanySetting::DEFAULT_ACCENT_COLOR;
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $colorColumn) {
            if (! Schema::hasColumn('company_settings', $colorColumn)) {
                unset($data[$colorColumn]);
            }
        }
        if (! Schema::hasColumn('company_settings', 'location')) {
            unset($data['location']);
        }
        $data['tax_name'] = $data['tax_name'] ?? '';
        $data['tax_rate'] = $data['tax_rate'] ?? 0;
        $settings->update($data);

        return back()->with('status', 'Company settings updated.');
    }

    public function storePaymentMethod(Request $request)
    {
        abort_unless(Schema::hasTable('payment_methods'), 404);

        PaymentMethod::create($this->paymentMethodData($request));

        return back()->with('status', 'Payment method saved.');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        abort_unless(Schema::hasTable('payment_methods'), 404);

        $paymentMethod->update($this->paymentMethodData($request));

        return back()->with('status', 'Payment method updated.');
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
        TermsCondition::create(array_replace($data, ['is_default' => $request->boolean('is_default')]));

        return back()->with('status', 'Terms saved.');
    }

    public function updateTerms(Request $request, TermsCondition $termsCondition)
    {
        abort_unless(Schema::hasTable('terms_conditions'), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            TermsCondition::whereKeyNot($termsCondition->id)->update(['is_default' => false]);
        }

        $termsCondition->update(array_replace($data, ['is_default' => $request->boolean('is_default')]));

        return back()->with('status', 'Terms updated.');
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

        if (! $request->filled('name') && ! $request->hasFile('signature') && ! $request->hasFile('stamp')) {
            return back()
                ->withErrors(['name' => 'Enter a name or upload a signature/stamp image.'])
                ->withInput();
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'],
            'stamp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = $request->boolean('is_default') || ! Signatory::where('is_active', true)->exists();

        if ($isDefault) {
            Signatory::where('is_default', true)->update(['is_default' => false]);
        }

        $signatoryData = [
            'name' => $data['name'] ?: ($this->activeCompanySettings()->company_name ?: 'Authorized Representative'),
            'title' => $data['title'] ?? null,
            'is_default' => $isDefault,
            'is_active' => true,
        ];

        if ($request->hasFile('signature') && Schema::hasColumn('signatories', 'signature_path')) {
            $signatoryData['signature_path'] = $request->file('signature')->store('signatures', 'public');
        }

        if ($request->hasFile('stamp') && Schema::hasColumn('signatories', 'stamp_path')) {
            $signatoryData['stamp_path'] = $request->file('stamp')->store('stamps', 'public');
        }

        Signatory::create($signatoryData);

        return back()->with('status', 'Signatory saved.');
    }

    public function updateSignatory(Request $request, Signatory $signatory)
    {
        abort_unless(Schema::hasTable('signatories'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'],
            'stamp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            Signatory::whereKeyNot($signatory->id)->update(['is_default' => false]);
        }

        $updates = [
            'name' => $data['name'],
            'title' => $data['title'] ?? null,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('signature')) {
            $updates['signature_path'] = $request->file('signature')->store('signatures', 'public');
        }

        if ($request->hasFile('stamp') && Schema::hasColumn('signatories', 'stamp_path')) {
            $updates['stamp_path'] = $request->file('stamp')->store('stamps', 'public');
        }

        $signatory->update($updates);

        return back()->with('status', 'Signatory updated.');
    }

    public function deleteSignatory(Signatory $signatory)
    {
        abort_unless(Schema::hasTable('signatories'), 404);

        $signatory->delete();

        return back()->with('status', 'Signatory removed.');
    }

    private function paymentMethodData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'details' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return array_replace($data, ['is_active' => $request->boolean('is_active')]);
    }

    private function activeCompanySettings(): CompanySetting
    {
        $defaults = ['company_name' => ActiveBusiness::current()?->name ?? 'Bama'];

        foreach ([
            'primary_color' => CompanySetting::DEFAULT_PRIMARY_COLOR,
            'secondary_color' => CompanySetting::DEFAULT_SECONDARY_COLOR,
            'accent_color' => CompanySetting::DEFAULT_ACCENT_COLOR,
        ] as $colorColumn => $default) {
            if (Schema::hasColumn('company_settings', $colorColumn)) {
                $defaults[$colorColumn] = $default;
            }
        }

        return CompanySetting::firstOrCreate(
            ['business_id' => ActiveBusiness::id()],
            $defaults
        );
    }

    private function profileUsers()
    {
        if (
            ! Schema::hasColumn('users', 'enable_otp_login')
            || ! Schema::hasTable('business_user')
            || ! ActiveBusiness::id()
        ) {
            return collect();
        }

        $userIds = DB::table('business_user')
            ->where('business_id', ActiveBusiness::id())
            ->pluck('user_id');

        return User::whereIn('id', $userIds)
            ->whereNotIn('role', ['super_admin', 'client_portal'])
            ->orderBy('name')
            ->get();
    }
}
