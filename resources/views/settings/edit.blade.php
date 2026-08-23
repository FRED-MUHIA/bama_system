@extends('layouts.app')
@section('title','Company Settings')
@section('content')
<div class="row g-4">
    <div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="h5">Company Profile</h2>
        <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Company name</label><input class="form-control" name="company_name" value="{{ old('company_name',$settings->company_name) }}" required></div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input class="form-control" type="file" name="logo" accept="image/*">
                    @if($settings->logoUrl())
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <img src="{{ $settings->logoUrl() }}" alt="Current logo" style="width:52px;height:52px;object-fit:contain;border:1px solid #e5e7eb;border-radius:6px;background:#fff;padding:4px">
                            <div class="small text-muted">Current logo is attached and will stay unless you upload a replacement.</div>
                        </div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Primary document color</label>
                    <div class="input-group">
                        <input class="form-control form-control-color" type="color" name="primary_color" value="{{ old('primary_color',$settings->primary_color ?? \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR) }}">
                        <input class="form-control" value="{{ old('primary_color',$settings->primary_color ?? \App\Models\CompanySetting::DEFAULT_PRIMARY_COLOR) }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Secondary document color</label>
                    <div class="input-group">
                        <input class="form-control form-control-color" type="color" name="secondary_color" value="{{ old('secondary_color',$settings->secondary_color ?? \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR) }}">
                        <input class="form-control" value="{{ old('secondary_color',$settings->secondary_color ?? \App\Models\CompanySetting::DEFAULT_SECONDARY_COLOR) }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Accent document color</label>
                    <div class="input-group">
                        <input class="form-control form-control-color" type="color" name="accent_color" value="{{ old('accent_color',$settings->accent_color ?? \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR) }}">
                        <input class="form-control" value="{{ old('accent_color',$settings->accent_color ?? \App\Models\CompanySetting::DEFAULT_ACCENT_COLOR) }}" disabled>
                    </div>
                </div>
                <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone',$settings->phone) }}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email',$settings->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" name="website" value="{{ old('website',$settings->website) }}"></div>
                <div class="col-md-3"><label class="form-label">Tax name</label><input class="form-control" name="tax_name" value="{{ old('tax_name',$settings->tax_name) }}" placeholder="Optional"></div>
                <div class="col-md-3"><label class="form-label">Tax rate %</label><input class="form-control" type="number" step="0.01" min="0" name="tax_rate" value="{{ old('tax_rate',$settings->tax_rate) }}" placeholder="0"></div>
                <div class="col-md-3"><label class="form-label">Currency</label><input class="form-control" name="currency_code" maxlength="3" value="{{ old('currency_code',$settings->currency_code ?? 'KES') }}" required></div>
                <div class="col-md-3"><label class="form-label">Locale</label><input class="form-control" name="locale" value="{{ old('locale',$settings->locale ?? 'en_KE') }}" required></div>
                <div class="col-md-6"><label class="form-label">Location</label><input class="form-control" name="location" value="{{ old('location',$settings->location) }}" placeholder="City, town, branch or site location"></div>
                <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address">{{ old('address',$settings->address) }}</textarea></div>
                <div class="col-12"><label class="form-label">Default terms and conditions</label><textarea class="form-control" name="default_terms" rows="4">{{ old('default_terms',$settings->default_terms) }}</textarea></div>
            </div>
            <button class="btn btn-warning mt-3">Save Settings</button>
        </form>
    </div></div></div>
    <div class="col-lg-5"><div class="card mb-4"><div class="card-body"><h2 class="h5">Invoice Payment Methods</h2>
        <form method="post" action="{{ route('payment-methods.store') }}" class="border-bottom pb-3 mb-3">@csrf
            <div class="row g-2"><div class="col-md-7"><input class="form-control" name="name" placeholder="Bank, M-Pesa, Cash" required></div><div class="col-md-5"><select class="form-select" name="type"><option value="bank">Bank</option><option value="mpesa">M-Pesa</option><option value="cash">Cash</option><option value="custom">Other</option></select></div></div>
            <textarea class="form-control mt-2" name="details" rows="3" placeholder="Account name, account number, Paybill/Till, branch, payment instructions"></textarea>
            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> <span class="form-check-label">Active</span></label>
            <button class="btn btn-outline-warning btn-sm mt-2">Add Method</button>
        </form>
        @foreach($methods as $method)<div class="border-top py-2 d-flex justify-content-between"><div><strong>{{ $method->name }}</strong><div class="small text-muted">{{ $method->type }} · {{ $method->details }}</div></div><form method="post" action="{{ route('payment-methods.destroy',$method) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></div>@endforeach
    </div></div>
    <div class="card mb-4"><div class="card-body"><h2 class="h5">Signatures & Stamps</h2>
        <form method="post" action="{{ route('signatories.store') }}" class="border-bottom pb-3 mb-3" enctype="multipart/form-data">@csrf
            <div class="row g-2"><div class="col-md-6"><input class="form-control" name="name" placeholder="Full name" required></div><div class="col-md-6"><input class="form-control" name="title" placeholder="Title (e.g. Managing Director)"></div></div>
            <label class="form-label mt-2">Signature</label><input class="form-control" type="file" name="signature" accept="image/*">
            <label class="form-label mt-2">Stamp</label><input class="form-control" type="file" name="stamp" accept="image/*">
            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_default" value="1"> <span class="form-check-label">Make default signatory</span></label>
            <button class="btn btn-outline-warning btn-sm mt-2">Add Signatory</button>
        </form>
        @foreach($signatories as $sig)
            <div class="border-top py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    @if($sig->signatureUrl())<img src="{{ $sig->signatureUrl() }}" style="max-height:36px;">@endif
                    @if($sig->stampUrl())<img src="{{ $sig->stampUrl() }}" style="max-height:42px;max-width:70px;object-fit:contain;">@endif
                    <div><strong>{{ $sig->name }}</strong><div class="small text-muted">{{ $sig->title }}</div></div>
                    @if($sig->is_default)<span class="badge bg-warning text-dark ms-1">Default</span>@endif
                </div>
                <form method="post" action="{{ route('signatories.destroy',$sig) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            </div>
        @endforeach
    </div></div>
    <div class="card"><div class="card-body"><h2 class="h5">Reusable Terms</h2>
        <form method="post" action="{{ route('terms.store') }}" class="border-bottom pb-3 mb-3">@csrf
            <input class="form-control mb-2" name="title" placeholder="Title" required><textarea class="form-control" name="content" placeholder="Terms content" required></textarea>
            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_default" value="1"> <span class="form-check-label">Make default</span></label>
            <button class="btn btn-outline-warning btn-sm mt-2">Add Terms</button>
        </form>
        @foreach($terms as $term)<div class="border-top py-2 d-flex justify-content-between"><div><strong>{{ $term->title }}</strong>@if($term->is_default)<span class="badge bg-warning text-dark ms-1">Default</span>@endif<div class="small text-muted">{{ $term->content }}</div></div><form method="post" action="{{ route('terms.destroy',$term) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></div>@endforeach
    </div></div></div>
</div>
@if($users->count())
<div class="card mt-4"><div class="card-body"><h2 class="h5">Authentication Methods</h2>
    @foreach($users as $user)
        <form class="border-top py-2" method="post" action="{{ route('erp.users.auth-settings',$user) }}">@csrf @method('PUT')
            <div class="row g-2 align-items-center">
                <div class="col-md-4"><strong>{{ $user->name }}</strong><div class="small text-muted">{{ $user->email }}</div></div>
                <div class="col-md-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="enable_password_login" value="1" @checked($user->enable_password_login)> Password</label></div>
                <div class="col-md-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="enable_otp_login" value="1" @checked($user->enable_otp_login)> OTP</label></div>
                <div class="col-md-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="enable_magic_link_login" value="1" @checked($user->enable_magic_link_login)> Magic Link</label></div>
                <div class="col-md-2"><button class="btn btn-outline-warning btn-sm">Save</button></div>
            </div>
        </form>
    @endforeach
</div></div>
@endif
@endsection
