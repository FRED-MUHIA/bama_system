@extends('layouts.app')

@section('title','My Profile')

@section('content')
<style>
    .profile-pref-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .profile-pref-card{border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#fbfcfd}
    .profile-pref-card .form-check{display:flex;align-items:flex-start;gap:8px;min-height:34px}
    .profile-pref-card .form-check-input{margin-top:.2rem;flex:0 0 auto}
    .profile-pref-icon{width:30px;height:30px;display:grid;place-items:center;border-radius:8px;background:#EAF8F0;color:#007A3B;flex:0 0 30px}
    .profile-pref-empty{border:1px dashed #cfd5df;border-radius:8px;padding:14px;color:#667085;background:#fff}
    @media(max-width:640px){.profile-pref-grid{grid-template-columns:1fr}}
</style>
@php
    $workspace = $industryWorkspace ?? [];
    $workspaceMenus = collect($workspace['menus'] ?? []);
    $workspaceWidgets = collect($workspace['widgets'] ?? []);
    $hiddenMenuKeys = collect($workspace['hidden_menu_keys'] ?? []);
    $hiddenWidgetSlugs = collect($workspace['hidden_widget_slugs'] ?? []);
    $enabledMenuKeys = collect(old('industry_workspace.enabled_menu_keys', $workspaceMenus->pluck('preference_key')->diff($hiddenMenuKeys)->values()->all()));
    $enabledWidgetSlugs = collect(old('industry_workspace.enabled_widget_slugs', $workspaceWidgets->pluck('slug')->diff($hiddenWidgetSlugs)->values()->all()));
    $componentDensity = old('industry_workspace.component_density', $workspace['component_density'] ?? 'comfortable');
@endphp
<div class="row justify-content-center g-3">
    <div class="col-lg-8">
        <div class="card p-4">
            <h1 class="h4">My profile</h1>
            <p class="text-muted">You can update personal preferences. Role, permissions, branch, department and approval level remain administrator-controlled.</p>
            <div class="mb-3">
                @include('mobile.install-card')
            </div>
            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input class="form-control" value="{{ $user->name }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input class="form-control" value="{{ $user->email }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Language</label>
                    <select class="form-select" name="preferred_language">
                        <option value="en" @selected($user->preferred_language === 'en')>English</option>
                        <option value="sw" @selected($user->preferred_language === 'sw')>Kiswahili</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time zone</label>
                    <input class="form-control" name="timezone" value="{{ old('timezone', $user->timezone) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Profile photo</label>
                    <input class="form-control" type="file" name="photo" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Signature</label>
                    <input class="form-control" type="file" name="signature" accept="image/*">
                </div>
                <div class="col-12">
                    <strong>Notifications</strong>
                    <div class="d-flex flex-wrap gap-4 mt-2">
                        @foreach(['email' => 'Email', 'approvals' => 'Approvals', 'projects' => 'Projects', 'security' => 'Security alerts'] as $key => $label)
                            <label><input type="checkbox" name="notification_preferences[{{ $key }}]" value="1" @checked(data_get($user->notification_preferences, $key))> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                @if(!empty($workspace))
                    <div class="col-12">
                        <hr>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <strong>My industry workspace</strong>
                                <div class="text-muted small">{{ $workspace['name'] }} features and dashboard components for your profile only.</div>
                            </div>
                            <span class="status-pill">Personal</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Component density</label>
                                <select class="form-select" name="industry_workspace[component_density]">
                                    <option value="comfortable" @selected($componentDensity === 'comfortable')>Comfortable</option>
                                    <option value="compact" @selected($componentDensity === 'compact')>Compact</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="small text-muted fw-semibold text-uppercase mb-2">Features in my sidebar</div>
                        @if($workspaceMenus->isNotEmpty())
                            <div class="profile-pref-grid">
                                @foreach($workspaceMenus as $item)
                                    <div class="profile-pref-card">
                                        <label class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="industry_workspace[enabled_menu_keys][]" value="{{ $item['preference_key'] }}" @checked($enabledMenuKeys->contains($item['preference_key']))>
                                            <span class="profile-pref-icon"><i class="bi {{ $item['icon'] ?? 'bi-grid' }}"></i></span>
                                            <span>
                                                <span class="fw-semibold d-block">{{ $item['label'] }}</span>
                                                <span class="text-muted small">{{ $item['route'] }}</span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="profile-pref-empty">No customizable industry features are available for this profile.</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <div class="small text-muted fw-semibold text-uppercase mb-2">Dashboard components</div>
                        @if($workspaceWidgets->isNotEmpty())
                            <div class="profile-pref-grid">
                                @foreach($workspaceWidgets as $widget)
                                    <div class="profile-pref-card">
                                        <label class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="industry_workspace[enabled_widget_slugs][]" value="{{ $widget->slug }}" @checked($enabledWidgetSlugs->contains($widget->slug))>
                                            <span class="profile-pref-icon"><i class="bi bi-grid-1x2"></i></span>
                                            <span>
                                                <span class="fw-semibold d-block">{{ $widget->name }}</span>
                                                <span class="text-muted small">{{ $widget->component ?: $widget->slug }}</span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="profile-pref-empty">Dashboard components will appear here after widgets are registered for this industry.</div>
                        @endif
                    </div>
                @endif
                <div class="col-12">
                    <button class="btn btn-warning">Save profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-4">
            <h2 class="h5">About Bama</h2>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="text-muted small">App Version</div>
                    <strong>{{ config('app.version', '1.0.0') }}</strong>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Build Version</div>
                    <strong>{{ file_exists(public_path('build/manifest.json')) ? filemtime(public_path('build/manifest.json')) : 'local' }}</strong>
                </div>
                @if($user->role === 'super_admin')
                    <div class="col-md-4">
                        <div class="text-muted small">Environment</div>
                        <strong>{{ app()->environment() }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($user->role !== 'super_admin')
        <div class="col-lg-8">
            <div class="card border-danger p-4">
                <h2 class="h5 text-danger">Delete account</h2>
                <p class="text-muted mb-3">Deleting your own account removes your access immediately. This email cannot be used to create a new account for four months.</p>
                <form method="post" action="{{ route('profile.destroy') }}" class="row g-3" onsubmit="return confirm('Delete your account now? You will need to wait four months before reusing this email.');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="confirm_delete" value="1">
                    <div class="col-md-8">
                        <label class="form-label">Current password</label>
                        <input class="form-control @error('current_password') is-invalid @enderror" type="password" name="current_password" autocomplete="current-password" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-outline-danger w-100">Delete my account</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
