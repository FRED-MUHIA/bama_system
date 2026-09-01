@extends('layouts.app')

@section('title','My Profile')

@section('content')
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
