@extends('layouts.app')
@section('title','Secure Access')
@section('content')
@php
    $otpAvailable = (bool) ($otpAvailable ?? false);
    $otpSent = (bool) session('otp_sent');
    $loginContext = $loginContext ?? session('otp_context', 'business');
    $publicLoginPrefix = request()->routeIs('public.*') ? 'public.' : '';
    $loginActions = [
        'password' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.store') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.store') : route($publicLoginPrefix.'login.store')),
        'otpRequest' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.otp.request') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.otp.request') : route($publicLoginPrefix.'login.otp.request')),
        'otpVerify' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.otp.verify') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.otp.verify') : route($publicLoginPrefix.'login.otp.verify')),
        'magic' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.magic.request') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.magic.request') : route($publicLoginPrefix.'login.magic.request')),
    ];
    $loginCopy = [
        'owner' => ['label' => 'Platform owner access', 'title' => 'Owner Console', 'intro' => 'Sign in to manage tenants, pricing, billing, pages, and platform controls.'],
        'portal' => ['label' => 'Client portal access', 'title' => 'Client Portal', 'intro' => 'Sign in to view your invited projects, invoices, receipts, and documents.'],
        'business' => ['label' => 'Bama secure access', 'title' => 'Welcome Back', 'intro' => 'Sign in to your business workspace and continue to the dashboard.'],
    ][$loginContext] ?? ['label' => 'Bama secure access', 'title' => 'Welcome Back', 'intro' => 'Sign in to your business workspace and continue to the dashboard.'];
    $brandLogoPath = 'images/bama-solutions-02.png';
    $brandLogoUrl = asset($brandLogoPath).'?v='.(file_exists(public_path($brandLogoPath)) ? filemtime(public_path($brandLogoPath)) : time());
@endphp

<style>
    html,
    body {
        width:100%;
        max-width:100%;
        overflow-x:hidden;
    }
    body,
    body:has(.website-login) { padding-bottom:0 !important; background:#050806 !important; }
    main > section,
    main > section:has(.website-login) { max-width:none; padding:0 !important; }
    main > section > .alert,
    main > section:has(.website-login) > .alert {
        position:fixed;
        top:14px;
        left:50%;
        z-index:30;
        width:min(620px, calc(100% - 32px));
        transform:translateX(-50%);
        box-shadow:0 14px 34px rgba(0,0,0,.14);
    }

    .website-login {
        min-height:100vh;
        min-height:100dvh;
        display:grid;
        grid-template-columns:minmax(390px,.82fr) minmax(520px,1.18fr);
        background:#050806;
        color:#f7f9f2;
    }
    .website-login * { letter-spacing:0; }
    .website-login-brand {
        position:relative;
        display:flex;
        min-height:100vh;
        min-height:100dvh;
        flex-direction:column;
        justify-content:space-between;
        padding:clamp(28px,4vw,54px);
        background:#071006;
        color:#fff;
        overflow:hidden;
        isolation:isolate;
    }
    .website-login-brand::before {
        content:"";
        position:absolute;
        inset:0;
        z-index:-1;
        background:radial-gradient(circle at 78% 8%,rgba(223,255,69,.12),transparent 30%);
    }
    .website-login-logo {
        display:inline-flex;
        flex:0 0 auto;
        width:clamp(120px,32vw,148px);
    }
    .website-login-logo .bama-brand-logo { width:100%; }
    .website-login-logo img {
        width:100%;
        max-width:none;
        height:auto;
        object-fit:contain;
    }
    .website-login-home {
        display:inline-flex;
        align-items:center;
        gap:8px;
        width:fit-content;
        margin-top:18px;
        color:#cbd5e1;
        font-size:.78rem;
        font-weight:800;
        text-decoration:none;
        text-transform:uppercase;
    }
    .website-login-home:hover { color:#fff; }
    .website-login-copy { max-width:510px; margin-block:auto; }
    .website-login-kicker {
        display:flex;
        align-items:center;
        gap:10px;
        color:#79D9A3;
        font-size:.72rem;
        font-weight:800;
        text-transform:uppercase;
    }
    .website-login-kicker::before {
        content:"";
        width:30px;
        height:2px;
        border-radius:99px;
        background:#00A651;
    }
    .website-login-copy h1 {
        margin:18px 0;
        color:#fff;
        font-size:clamp(2.4rem,4.4vw,4.6rem);
        font-weight:700 !important;
        line-height:1.02;
    }
    .website-login-copy p {
        max-width:460px;
        color:#cbd5e1;
        font-size:1rem;
        line-height:1.7;
    }
    .website-login-meta {
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }
    .website-login-meta span {
        border:1px solid rgba(121,217,163,.28);
        border-radius:8px;
        padding:7px 10px;
        color:#dffdea;
        background:rgba(0,166,81,.12);
        font-size:.68rem;
        font-weight:800;
        text-transform:uppercase;
    }

    .website-login-panel {
        display:grid;
        min-height:100vh;
        place-items:center;
        padding:clamp(24px,4vw,54px);
        background:
            radial-gradient(circle at 50% 10%,rgba(223,255,69,.1),transparent 25rem),
            linear-gradient(180deg,#0a1008,#050806 72%);
    }
    .website-login-wrap { width:min(100%,420px); min-width:0; }
    .website-login-label {
        color:#dfff45;
        font-size:.72rem;
        font-weight:800;
        text-transform:uppercase;
        margin-bottom:8px;
    }
    .website-login-wrap > h2 {
        margin-bottom:8px;
        color:#f7f9f2;
        font-size:clamp(2rem,3.4vw,3rem);
        font-weight:700 !important;
        line-height:1.05;
    }
    .website-login-intro {
        margin-bottom:22px;
        color:#969d91;
        line-height:1.6;
    }
    .website-login-card {
        padding:20px;
        border:1px solid rgba(223,255,69,.09);
        border-radius:16px;
        background:linear-gradient(180deg,rgba(13,18,11,.86),rgba(5,8,6,.96));
        box-shadow:0 24px 64px rgba(0,0,0,.3);
    }
    .website-login-tabs {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:4px;
        padding:4px;
        margin-bottom:18px !important;
        border-radius:8px;
        background:rgba(255,255,255,.045);
    }
    .website-login-tabs .nav-item { display:grid; }
    .website-login-tabs .nav-link {
        border-radius:7px;
        color:#c7ccc3;
        padding:.62rem .45rem;
        font-size:.82rem;
        font-weight:800;
    }
    .website-login-tabs .nav-link.active {
        background:#dfff45;
        color:#071006;
        box-shadow:0 6px 18px rgba(202,246,50,.12);
    }
    .website-login-card .form-label {
        display:block;
        margin-bottom:7px;
        color:#dfff45 !important;
        font-size:.78rem;
        font-weight:800;
        text-transform:uppercase;
    }
    .website-login-card .form-control {
        display:block;
        width:100%;
        min-height:50px;
        border:1px solid #2c332a;
        border-radius:12px;
        background:rgba(2,5,3,.78);
        color:#f7f9f2 !important;
        -webkit-text-fill-color:#f7f9f2;
        padding-inline:15px;
        font-size:16px;
    }
    .website-login-card .form-control:focus {
        border-color:#dfff45;
        background:#090d08;
        color:#f7f9f2 !important;
        -webkit-text-fill-color:#f7f9f2;
        box-shadow:0 0 0 .2rem rgba(223,255,69,.13);
    }
    .website-login-card .form-control::placeholder {
        color:rgba(247,249,242,.48) !important;
        -webkit-text-fill-color:rgba(247,249,242,.48);
    }
    .website-login-card .form-control:-webkit-autofill,
    .website-login-card .form-control:-webkit-autofill:hover,
    .website-login-card .form-control:-webkit-autofill:focus {
        -webkit-text-fill-color:#f7f9f2 !important;
        box-shadow:0 0 0 1000px #090d08 inset, 0 0 0 .2rem rgba(223,255,69,.13);
        caret-color:#f7f9f2;
    }
    .website-login-card a {
        color:#dfff45;
        font-weight:800;
    }
    .website-login-card .btn-link {
        color:#dfff45 !important;
        font-weight:800;
        text-decoration-thickness:2px;
        text-underline-offset:4px;
    }
    .website-login-card .btn-link:disabled,
    .website-login-card .text-muted,
    .website-login-card [class*="text-slate-"],
    .website-login-card [class*="text-gray-"],
    .website-login-card [class*="text-zinc-"],
    .website-login-card [class*="text-blue-"],
    .website-login-card [class*="text-indigo-"] {
        color:#cfd7c7 !important;
    }
    .website-login-card .btn-warning {
        display:flex;
        align-items:center;
        justify-content:center;
        width:100%;
        min-height:50px;
        border:0;
        border-radius:12px;
        background:linear-gradient(90deg,#c8f32f,#e8ff59);
        color:#071006;
        font-size:.9rem;
        font-weight:600;
        box-shadow:0 12px 28px rgba(202,246,50,.14);
    }
    .website-login-card .btn-warning:hover,
    .website-login-card .btn-warning:focus {
        background:#edff77;
        color:#071006;
    }
    .password-wrap { position:relative; }
    .password-wrap .form-control { padding-right:46px; }
    .password-toggle {
        position:absolute;
        right:3px;
        top:3px;
        width:44px;
        height:44px;
        border:0;
        border-radius:8px;
        background:transparent;
        color:#aeb6aa;
    }
    .password-toggle:hover {
        background:rgba(255,255,255,.08);
        color:#fff;
    }
    .website-login-note {
        display:flex;
        align-items:center;
        gap:9px;
        margin-top:16px;
        color:#92998e;
        font-size:.78rem;
    }
    .website-login-note i { color:#00A651; }
    .website-login-card form { display:grid; gap:16px; }
    .website-login-card form > .mb-3,
    .website-login-card form > .mb-4 { margin-bottom:0 !important; }
    .website-login-card form > .d-flex {
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        justify-content:space-between;
        gap:8px;
    }
    .website-login-card .form-check { display:flex; align-items:center; gap:7px; }
    .website-login-card .form-check-input { width:16px; height:16px; margin:0; }
    .website-login-card .tab-content > .tab-pane { display:none; }
    .website-login-card .tab-content > .tab-pane.active { display:block; }

    @media (max-width:980px) {
        .website-login {
            grid-template-columns:1fr;
            grid-template-rows:auto 1fr;
            align-content:start;
        }
        .website-login-brand {
            min-height:auto;
            align-self:start;
            justify-content:flex-start;
            padding:max(16px, env(safe-area-inset-top)) 24px 12px;
        }
        .website-login-brand > div:first-child {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
        }
        .website-login-copy,
        .website-login-meta { display:none; }
        .website-login-home { min-height:44px; margin-top:0; }
        .website-login-panel {
            min-height:auto;
            place-items:start center;
            padding:16px 24px max(24px, env(safe-area-inset-bottom));
        }
        .website-login-wrap > h2 { font-size:clamp(1.75rem,7vw,2.125rem); }
        .website-login-intro { margin-bottom:16px; line-height:1.45; }
    }
    @media (max-width:680px) {
        .website-login-brand { padding-inline:16px; }
        .website-login-panel { padding:12px 16px max(20px, env(safe-area-inset-bottom)); }
        .website-login-label { display:none; }
        .website-login-wrap > h2,
        .website-login-intro { text-align:center; }
        .website-login-card { padding:0; border:0; background:transparent; box-shadow:none; }
    }
</style>

<x-auth-layout variant="bare">
<div class="website-login">
    <section class="website-login-brand" aria-label="Bama website access">
        <div>
            <div class="website-login-logo" aria-label="Bama secure access">
                <x-bama-logo variant="compact" :src="$brandLogoUrl" alt="BAMA" />
            </div>
        </div>

        <div class="website-login-copy">
            <div class="website-login-kicker">{{ $loginCopy['label'] }}</div>
            <h1>Secure access for connected operations.</h1>
            <p>{{ $loginCopy['intro'] }}</p>
        </div>

        <div class="website-login-meta">
            <span>Encrypted access</span>
            <span>Role controlled</span>
            <span>Activity audited</span>
            <span>Dashboard ready</span>
        </div>
    </section>

    <section class="website-login-panel">
        <div class="website-login-wrap">
            <div class="website-login-label">{{ $loginCopy['label'] }}</div>
            <h2>{{ $loginCopy['title'] }}</h2>
            <p class="website-login-intro">{{ $loginCopy['intro'] }}</p>

            <div class="website-login-card">
                @if ($otpAvailable)
                    <ul class="nav nav-pills website-login-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link {{ $otpSent ? '' : 'active' }}" data-bs-toggle="pill" data-bs-target="#password-login" type="button">Password</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $otpSent ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#otp-login" type="button">OTP</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#magic-login" type="button">Magic link</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade {{ $otpSent ? '' : 'show active' }}" id="password-login">
                @endif

                <form method="post" action="{{ $loginActions['password'] }}">
                    @csrf
                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                    <div class="mb-3">
                        <label class="form-label">Email or Username</label>
                        <input name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="password-wrap">
                            <input id="login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="login-password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember">
                            <span class="form-check-label">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                    <button class="btn btn-warning w-100"><i class="bi bi-stars me-2"></i> Sign in</button>
                </form>

                @if ($otpAvailable)
                        </div>
                        <div class="tab-pane fade {{ $otpSent ? 'show active' : '' }}" id="otp-login">
                            @if ($otpSent)
                                <div class="alert alert-success">
                                    <strong>OTP sent</strong><br>
                                    <small>We sent a 6-digit code to {{ session('otp_email') }}.</small>
                                </div>
                                <form method="post" action="{{ $loginActions['otpVerify'] }}">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                    <div class="mb-3">
                                        <label class="form-label">Verification code</label>
                                        <input class="form-control text-center fs-4" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                                    </div>
                                    <button class="btn btn-warning w-100">Verify OTP</button>
                                </form>
                                <div class="text-center mt-3">
                                    <small class="text-muted d-block mb-2">Did not receive it? Check your spam folder.</small>
                                    <form method="post" action="{{ $loginActions['otpRequest'] }}">
                                        @csrf
                                        <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                        <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                        <button id="resend-otp" class="btn btn-link" disabled data-ready-at="{{ session('otp_resend_at') }}">Resend OTP in <span id="otp-countdown">60</span>s</button>
                                    </form>
                                </div>
                            @else
                                <form method="post" action="{{ $loginActions['otpRequest'] }}">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <div class="mb-3">
                                        <label class="form-label">Work email</label>
                                        <input class="form-control" name="email" type="email" value="{{ old('email') }}" required>
                                    </div>
                                    <button class="btn btn-warning w-100">Send one-time code</button>
                                </form>
                            @endif
                        </div>
                        <div class="tab-pane fade" id="magic-login">
                            <form method="post" action="{{ $loginActions['magic'] }}">
                                @csrf
                                <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                <div class="mb-3">
                                    <label class="form-label">Work email</label>
                                    <input class="form-control" name="email" type="email" required>
                                </div>
                                <button class="btn btn-warning w-100">Email secure login link</button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="website-login-note">
                    <i class="bi bi-shield-check"></i>
                    <span>Your access is encrypted, role-controlled, and recorded for security.</span>
                </div>
                @if($loginContext === 'business')
                    <div class="text-center mt-3">
                        <span class="text-muted">Do not have an account?</span>
                        <a href="{{ route('register.account') }}">Create Account</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const loginTabs = document.querySelectorAll('.website-login-tabs [data-bs-target]');
        loginTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                loginTabs.forEach((item) => {
                    item.classList.toggle('active', item === tab);
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                });
                document.querySelectorAll('.website-login-card .tab-pane').forEach((panel) => {
                    const active = `#${panel.id}` === tab.dataset.bsTarget;
                    panel.classList.toggle('active', active);
                    panel.classList.toggle('show', active);
                });
            });
        });

        @if ($otpSent)
            const button = document.querySelector('#resend-otp');
            const countdown = document.querySelector('#otp-countdown');
            if (button && countdown) {
                const readyAt = Number(button.dataset.readyAt) * 1000;
                const tick = () => {
                    const seconds = Math.max(0, Math.ceil((readyAt - Date.now()) / 1000));
                    countdown.textContent = seconds;
                    if (seconds === 0) {
                        button.disabled = false;
                        button.textContent = 'Resend OTP';
                        return;
                    }
                    setTimeout(tick, 250);
                };
                tick();
            }
        @endif
    });
</script>
</x-auth-layout>
@endsection
