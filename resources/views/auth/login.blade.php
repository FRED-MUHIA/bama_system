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
        'owner' => [
            'label' => 'Owner console',
            'headline' => ['Manage', 'Platform', 'Access'],
            'intro' => 'Sign in to control tenants, plans, billing, pages, security, and platform settings.',
            'formTitle' => 'Owner sign in',
            'button' => 'Open owner dashboard',
            'create' => null,
        ],
        'portal' => [
            'label' => 'Client portal',
            'headline' => ['View', 'Your', 'Workspace'],
            'intro' => 'Sign in to view invited projects, invoices, receipts, and secure documents.',
            'formTitle' => 'Portal sign in',
            'button' => 'Open portal dashboard',
            'create' => null,
        ],
        'business' => [
            'label' => 'BAMA Workspace',
            'headline' => ['Manage', 'Your', 'Business'],
            'intro' => 'Sign up or log in to see business activity, finance, clients, stock, projects, and reports in one dashboard.',
            'formTitle' => 'Continue with email',
            'button' => 'Sign in',
            'create' => 'Create business account',
        ],
    ][$loginContext] ?? [
        'label' => 'BAMA Workspace',
        'headline' => ['Manage', 'Your', 'Business'],
        'intro' => 'Sign up or log in to see business activity, finance, clients, stock, projects, and reports in one dashboard.',
        'formTitle' => 'Continue with email',
        'button' => 'Sign in',
        'create' => 'Create business account',
    ];
    $system = $loginSystem ?? ['workspaces' => 'Ready', 'modules' => 'Live', 'industries' => 'Many', 'security' => 'Encrypted'];
    $brandLogoPath = 'images/bama-solutions-02.png';
    $brandLogoUrl = asset($brandLogoPath).'?v='.(file_exists(public_path($brandLogoPath)) ? filemtime(public_path($brandLogoPath)) : time());
    $heroImagePath = 'images/analytics-command-center.png';
    $heroImageUrl = asset($heroImagePath).'?v='.(file_exists(public_path($heroImagePath)) ? filemtime(public_path($heroImagePath)) : time());
@endphp

<style>
    body:has(.app-login) { padding-bottom:0 !important; background:#071B12 !important; }
    main > section:has(.app-login) { max-width:none; padding:0 !important; overflow:hidden; }
    main > section:has(.app-login) > .alert {
        position:fixed;
        top:calc(12px + env(safe-area-inset-top));
        left:50%;
        z-index:50;
        width:min(640px, calc(100% - 28px));
        transform:translateX(-50%);
        border-radius:8px;
        box-shadow:0 20px 45px rgba(0,0,0,.22);
    }
    body:has(.app-login) .guest-theme-toggle { display:none !important; }

    .app-login {
        --teal:#31c6bb;
        --teal-deep:#0fa899;
        --night:#061512;
        --ink:#ffffff;
        --muted:rgba(255,255,255,.76);
        min-height:100vh;
        min-height:100dvh;
        color:var(--ink);
        background:#071B12;
        display:grid;
        grid-template-columns:minmax(420px,.9fr) minmax(460px,.82fr);
        isolation:isolate;
    }
    .app-login * { letter-spacing:0; }

    .app-login-hero {
        position:relative;
        min-height:100vh;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        padding:clamp(30px,4vw,56px);
        overflow:hidden;
        background:#071B12;
    }
    .app-login-hero::before {
        content:"";
        position:absolute;
        inset:0;
        z-index:-3;
        background:
            linear-gradient(180deg,rgba(4,22,18,.32),rgba(4,14,12,.96)),
            linear-gradient(90deg,rgba(3,20,16,.92),rgba(3,20,16,.26) 56%,rgba(3,20,16,.84)),
            url("{{ $heroImageUrl }}") center/cover no-repeat;
        filter:saturate(.95) contrast(1.04);
    }
    .app-login-hero::after {
        content:"";
        position:absolute;
        inset:0;
        z-index:-2;
        background:
            radial-gradient(circle at 72% 12%,rgba(49,198,187,.38),transparent 28%),
            linear-gradient(180deg,rgba(49,198,187,.14),transparent 34%);
        pointer-events:none;
    }

    .app-login-brand {
        display:flex;
        align-items:center;
        justify-content:center;
        gap:13px;
        font-size:1.18rem;
        font-weight:800;
        text-decoration:none;
        color:#fff;
    }
    .app-login-brand img {
        width:154px;
        height:auto;
        object-fit:contain;
        filter:drop-shadow(0 12px 24px rgba(0,0,0,.18));
    }

    .app-login-headline {
        max-width:680px;
        margin-block:auto 48px;
    }
    .app-login-kicker {
        display:inline-flex;
        align-items:center;
        gap:8px;
        margin-bottom:24px;
        color:rgba(255,255,255,.9);
        font-size:.76rem;
        font-weight:800;
        text-transform:uppercase;
    }
    .app-login-kicker::before {
        content:"";
        width:28px;
        height:2px;
        border-radius:999px;
        background:var(--teal);
    }
    .app-login-headline h1 {
        margin:0;
        max-width:520px;
        color:#fff;
        font-size:clamp(4.2rem,8.5vw,8rem);
        font-weight:800 !important;
        line-height:.94;
        text-shadow:0 18px 40px rgba(0,0,0,.34);
    }
    .app-login-headline p {
        max-width:520px;
        margin:28px 0 0;
        color:var(--muted);
        font-size:1.02rem;
        line-height:1.68;
    }
    .app-login-start {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:10px;
        min-height:66px;
        width:min(100%,520px);
        margin-top:34px;
        border:0;
        border-radius:999px;
        background:linear-gradient(135deg,#3dd1c6,#11aa9c);
        color:#fff;
        font-size:1.04rem;
        font-weight:900;
        text-decoration:none;
        box-shadow:0 18px 44px rgba(17,170,156,.34);
    }
    .app-login-start:hover { color:#fff; filter:brightness(1.04); }

    .app-login-status {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:9px;
        max-width:680px;
    }
    .app-login-status span {
        min-height:70px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        gap:3px;
        border:1px solid rgba(255,255,255,.12);
        border-radius:8px;
        padding:11px 12px;
        background:rgba(3,16,14,.58);
        backdrop-filter:blur(14px);
    }
    .app-login-status strong {
        color:#fff;
        font-size:1.18rem;
        font-weight:900;
        line-height:1;
    }
    .app-login-status small {
        color:rgba(255,255,255,.72);
        font-size:.65rem;
        font-weight:800;
        text-transform:uppercase;
    }

    .app-login-panel {
        position:relative;
        min-height:100vh;
        display:grid;
        place-items:center;
        padding:clamp(28px,4vw,58px);
        background:
            radial-gradient(circle at 80% 10%,rgba(49,198,187,.18),transparent 32%),
            linear-gradient(180deg,#0b211e,#061512);
    }
    .app-login-panel::before {
        content:"";
        position:absolute;
        inset:0;
        background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
        background-size:32px 32px;
        mask-image:linear-gradient(180deg,#000,transparent 82%);
        pointer-events:none;
    }
    .app-login-card-wrap {
        position:relative;
        width:min(100%,520px);
        z-index:1;
    }
    .app-login-form-heading {
        margin-bottom:18px;
        text-align:center;
    }
    .app-login-form-heading .label {
        color:var(--teal);
        font-size:.72rem;
        font-weight:900;
        text-transform:uppercase;
    }
    .app-login-form-heading h2 {
        margin:8px 0 0;
        color:#fff;
        font-size:clamp(1.7rem,3vw,2.55rem);
        font-weight:800 !important;
    }

    .app-login-email-pill {
        width:100%;
        min-height:64px;
        border:0;
        border-radius:999px;
        background:linear-gradient(135deg,#3dd1c6,#11aa9c);
        color:#fff;
        font-size:1.02rem;
        font-weight:900;
        box-shadow:0 18px 44px rgba(17,170,156,.3);
    }

    .app-login-card {
        margin-top:24px;
        padding:26px;
        border:1px solid rgba(255,255,255,.08);
        border-radius:26px 26px 8px 8px;
        background:rgba(2,15,13,.91);
        box-shadow:0 30px 70px rgba(0,0,0,.28);
        backdrop-filter:blur(16px);
    }
    .app-login-tabs {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:5px;
        margin:0 0 20px !important;
        padding:5px;
        border:1px solid rgba(255,255,255,.08);
        border-radius:999px;
        background:rgba(255,255,255,.045);
    }
    .app-login-tabs .nav-item { display:grid; }
    .app-login-tabs .nav-link {
        min-height:42px;
        border-radius:999px;
        color:rgba(255,255,255,.78);
        font-size:.75rem;
        font-weight:900;
        padding:.42rem .35rem;
        white-space:nowrap;
    }
    .app-login-tabs .nav-link.active {
        background:var(--teal);
        color:#061512;
    }

    .app-login-card .form-label {
        margin-bottom:10px;
        color:#fff;
        font-size:.92rem;
        font-weight:900;
    }
    .app-login-card .form-control {
        min-height:64px;
        border:1px solid rgba(255,255,255,.16);
        border-radius:24px;
        background:rgba(255,255,255,.11);
        color:#fff;
        font-size:1rem;
        padding:1rem 1.18rem;
        box-shadow:inset 0 0 0 1px rgba(255,255,255,.04);
    }
    .app-login-card .form-control::placeholder { color:rgba(255,255,255,.36); }
    .app-login-card .form-control:focus {
        border-color:var(--teal);
        background:rgba(255,255,255,.14);
        color:#fff;
        box-shadow:0 0 0 .22rem rgba(49,198,187,.18);
    }
    .app-login-card .form-check-input {
        border-color:rgba(255,255,255,.4);
        background-color:rgba(255,255,255,.08);
    }
    .app-login-card .form-check-input:checked {
        background-color:var(--teal);
        border-color:var(--teal);
    }
    .app-login-card .form-check-label,
    .app-login-card .text-muted {
        color:rgba(255,255,255,.72) !important;
    }
    .app-login-card a {
        color:#dffdfa;
        font-weight:900;
        text-decoration-thickness:2px;
        text-underline-offset:4px;
    }
    .app-login-submit,
    .app-login-card .btn-warning {
        min-height:64px;
        border:0;
        border-radius:999px;
        background:linear-gradient(135deg,#3dd1c6,#11aa9c);
        color:#fff;
        font-size:1.02rem;
        font-weight:900;
        box-shadow:0 18px 44px rgba(17,170,156,.28);
    }
    .app-login-submit:hover,
    .app-login-submit:focus,
    .app-login-card .btn-warning:hover,
    .app-login-card .btn-warning:focus {
        background:linear-gradient(135deg,#48ddd2,#0fa899);
        color:#fff;
    }
    .app-login-create {
        min-height:62px;
        border-radius:999px;
        border:0;
        background:#fff;
        color:#071B12 !important;
        font-size:.98rem;
        font-weight:950;
        text-decoration:none !important;
    }
    .app-login-create:hover { color:#071B12 !important; background:#f5faf9; }

    .password-wrap { position:relative; }
    .password-wrap .form-control { padding-right:58px; }
    .password-toggle {
        position:absolute;
        top:50%;
        right:12px;
        width:42px;
        height:42px;
        transform:translateY(-50%);
        border:0;
        border-radius:50%;
        background:rgba(255,255,255,.08);
        color:#fff;
    }
    .password-toggle:hover { background:rgba(49,198,187,.18); color:#fff; }

    .app-login-security {
        display:flex;
        align-items:flex-start;
        gap:9px;
        margin-top:20px;
        color:rgba(255,255,255,.66);
        font-size:.78rem;
        line-height:1.45;
    }
    .app-login-security i { color:var(--teal); font-size:1rem; margin-top:1px; }
    .app-login-legal {
        max-width:420px;
        margin:30px auto 0;
        color:rgba(255,255,255,.62);
        font-size:.82rem;
        line-height:1.55;
        text-align:center;
    }
    .app-login-legal a { color:#9df4ee; font-weight:800; text-decoration:none; }
    .otp-success {
        border-color:rgba(49,198,187,.32);
        background:rgba(49,198,187,.12);
        color:#dffdfa;
    }

    html[data-theme="dark"] .app-login,
    html[data-theme="dark"] .app-login-panel,
    html[data-theme="dark"] .app-login-hero {
        color:#fff !important;
    }

    @media (max-width: 1040px) {
        .app-login { grid-template-columns:1fr; }
        .app-login-hero {
            min-height:56vh;
            padding:calc(24px + env(safe-area-inset-top)) 24px 88px;
        }
        .app-login-headline {
            margin-block:72px 30px;
        }
        .app-login-headline h1 { font-size:clamp(3.4rem,13vw,6.2rem); }
        .app-login-panel {
            min-height:auto;
            margin-top:-64px;
            padding:0 18px calc(28px + env(safe-area-inset-bottom));
            border-radius:34px 34px 0 0;
            background:#061512;
        }
        .app-login-panel::before { display:none; }
        .app-login-card-wrap { padding-top:28px; }
    }

    @media (max-width: 680px) {
        .app-login { display:block; min-height:100dvh; }
        .app-login-hero {
            min-height:55vh;
            padding-inline:20px;
            background:#071B12;
        }
        .app-login-brand { justify-content:center; }
        .app-login-brand img { width:148px; }
        .app-login-kicker { margin-bottom:18px; font-size:.68rem; }
        .app-login-headline { margin-block:68px 0; }
        .app-login-headline h1 {
            max-width:320px;
            font-size:clamp(4rem,21vw,6.4rem);
        }
        .app-login-headline p {
            max-width:340px;
            margin-top:20px;
            font-size:.95rem;
            line-height:1.52;
        }
        .app-login-start {
            min-height:60px;
            margin-top:28px;
            font-size:.98rem;
        }
        .app-login-status { display:none; }
        .app-login-card { padding:22px 18px; border-radius:26px 26px 0 0; }
        .app-login-form-heading { display:none; }
        .app-login-card .form-control,
        .app-login-email-pill,
        .app-login-submit,
        .app-login-card .btn-warning {
            min-height:58px;
        }
        .app-login-card .form-control { border-radius:22px; }
        .app-login-tabs .nav-link { font-size:.68rem; }
        .app-login-legal { margin-top:24px; font-size:.76rem; }
    }

    @media (max-width: 380px) {
        .app-login-hero { padding-inline:16px; }
        .app-login-card { padding-inline:14px; }
        .app-login-tabs { border-radius:18px; }
        .app-login-tabs .nav-link { white-space:normal; line-height:1.05; }
    }

    @media (prefers-reduced-motion: reduce) {
        .app-login-start,
        .app-login-submit,
        .app-login-card .btn-warning { transition:none; }
    }
</style>

<div class="app-login">
    <section class="app-login-hero" aria-label="BAMA app access">
        <div class="app-login-brand" aria-label="BAMA">
            <img src="{{ $brandLogoUrl }}" alt="Bama Solutions">
        </div>

        <div class="app-login-headline">
            <div class="app-login-kicker">{{ $loginCopy['label'] }}</div>
            <h1>{{ $loginCopy['headline'][0] }}<br>{{ $loginCopy['headline'][1] }}<br>{{ $loginCopy['headline'][2] }}</h1>
            <p>{{ $loginCopy['intro'] }}</p>
            <a class="app-login-start d-lg-none" href="#app-login-form">
                Get Started <i class="bi bi-arrow-down"></i>
            </a>
        </div>

        <div class="app-login-status" aria-label="Live system snapshot">
            <span><strong>{{ $system['workspaces'] }}</strong><small>Workspaces</small></span>
            <span><strong>{{ $system['modules'] }}</strong><small>Modules</small></span>
            <span><strong>{{ $system['industries'] }}</strong><small>Industries</small></span>
            <span><strong>{{ $system['security'] }}</strong><small>Access</small></span>
        </div>
    </section>

    <section class="app-login-panel" id="app-login-form">
        <div class="app-login-card-wrap">
            <div class="app-login-form-heading">
                <div class="label">App login</div>
                <h2>{{ $loginCopy['formTitle'] }}</h2>
            </div>

            <button class="app-login-email-pill" type="button" data-focus-login>
                Continue With Email
            </button>

            <div class="app-login-card">
                @if ($otpAvailable)
                    <ul class="nav nav-pills app-login-tabs" role="tablist">
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
                        <label class="form-label">Email address</label>
                        <input name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="password-wrap">
                            <input id="login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="login-password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button class="btn app-login-submit w-100" type="submit">{{ $loginCopy['button'] }}</button>
                    @if($loginCopy['create'])
                        <a href="{{ route('register.account') }}" class="btn app-login-create w-100 mt-3">{{ $loginCopy['create'] }}</a>
                    @endif
                    <div class="text-center mt-4">
                        <a href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                    <label class="form-check d-flex justify-content-center gap-2 mt-3">
                        <input class="form-check-input" type="checkbox" name="remember">
                        <span class="form-check-label">Keep me signed in</span>
                    </label>
                </form>

                @if ($otpAvailable)
                        </div>
                        <div class="tab-pane fade {{ $otpSent ? 'show active' : '' }}" id="otp-login">
                            @if ($otpSent)
                                <div class="alert otp-success">
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
                                    <small class="text-muted d-block mb-2">Check your inbox and spam folder.</small>
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

                <div class="app-login-security">
                    <i class="bi bi-shield-check"></i>
                    <span>Accounts are checked against the system database, tenant context is loaded, and successful sign-ins open the dashboard.</span>
                </div>
                @include('mobile.install-card')
            </div>

            <div class="app-login-legal">
                By signing up or logging in, I accept the BAMA Terms of Service and Privacy Policy.
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const focusButton = document.querySelector('[data-focus-login]');
        const username = document.querySelector('input[name="username"]');
        focusButton?.addEventListener('click', () => {
            document.querySelector('#app-login-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => username?.focus({ preventScroll: true }), 280);
        });

        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            const password = document.getElementById(toggle.dataset.passwordToggle);
            if (! password) return;

            toggle.addEventListener('click', () => {
                const show = password.type === 'password';
                password.type = show ? 'text' : 'password';
                toggle.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
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
@endsection
