@extends('layouts.app')
@section('title','Secure Access')
@section('content')
@php($otpAvailable = \Illuminate\Support\Facades\Schema::hasTable('otp_codes'))
@php($otpSent = (bool) session('otp_sent'))
@php($loginContext = $loginContext ?? session('otp_context', 'business'))
@php($publicLoginPrefix = request()->routeIs('public.*') ? 'public.' : '')
@php($loginActions = [
    'password' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.store') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.store') : route($publicLoginPrefix.'login.store')),
    'otpRequest' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.otp.request') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.otp.request') : route($publicLoginPrefix.'login.otp.request')),
    'otpVerify' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.otp.verify') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.otp.verify') : route($publicLoginPrefix.'login.otp.verify')),
    'magic' => $loginContext === 'owner' ? route($publicLoginPrefix.'platform.login.magic.request') : ($loginContext === 'portal' ? route($publicLoginPrefix.'portal.login.magic.request') : route($publicLoginPrefix.'login.magic.request')),
])
@php($loginCopy = [
    'owner' => ['label' => 'Platform owner access', 'title' => 'Owner console.', 'intro' => 'Sign in to manage tenants, clients, pricing, and platform controls.'],
    'portal' => ['label' => 'Client portal access', 'title' => 'Client portal.', 'intro' => 'Sign in to view your invited projects, invoices, and documents.'],
    'business' => ['label' => 'Identity & Access', 'title' => 'Welcome back.', 'intro' => 'Choose a secure sign-in method to continue to your workspace.'],
][$loginContext] ?? ['label' => 'Identity & Access', 'title' => 'Welcome back.', 'intro' => 'Choose a secure sign-in method to continue to your workspace.'])
@php($brandLogoUrl = \App\Support\PublicUpload::url('logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png') ?: asset('images/bama-solutions-02.png'))

<style>
    body:has(.login-stage) { padding-bottom: 0 !important; }
    main > section:has(.login-stage) { max-width: none; padding: 0 !important; }
    main > section:has(.login-stage) > .alert {
        position: fixed;
        top: 14px;
        left: 50%;
        z-index: 20;
        width: min(620px, calc(100% - 32px));
        transform: translateX(-50%);
        box-shadow: 0 14px 34px rgba(0, 0, 0, .14);
    }

    body:has(.login-stage) .guest-theme-toggle {
        display: none !important;
    }

    .login-stage {
        --green: #00A651;
        --green-soft: #EAF8F0;
        --black: #000000;
        --line: #e5e7eb;
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(390px, .75fr) minmax(520px, 1.25fr);
        background: #F7F8F5;
        color: #000;
    }

    .login-stage * { letter-spacing: 0; }

    .login-stage .font-weight-bold,
    .login-stage .fw-bold,
    .login-stage strong {
        font-weight: 500 !important;
    }

    .login-brand-panel {
        position: relative;
        overflow: hidden;
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        justify-content: space-between;
        padding: clamp(28px, 4vw, 54px);
        color: #000;
        background: #ffffff;
        border-right: 1px solid #e5e7eb;
        isolation: isolate;
    }

    .login-brand-panel::after {
        display: none;
    }

    .login-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 800;
    }

    .login-brand-panel a.login-brand,
    .login-mobile-brand {
        color: #000;
        text-decoration: none;
    }

    .login-home-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        margin-top: 18px;
        color: #000;
        font-size: .78rem;
        font-weight: 800;
        text-decoration: none;
        text-transform: uppercase;
    }

    .login-home-link:hover {
        color: var(--green);
    }

    .login-brand .brand-mark,
    .login-mobile-brand .brand-mark {
        display: block;
        width: 76px;
        height: 76px;
        object-fit: contain;
    }

    .login-strips {
        display: none;
        grid-template-columns: 1.1fr .9fr .75fr .6fr .5fr;
        gap: 6px;
        width: min(100%, 360px);
        margin-top: 22px;
    }

    .login-strips span {
        height: 7px;
        border-radius: 999px;
    }

    .login-copy {
        position: relative;
        z-index: 2;
        max-width: 500px;
        margin-block: auto;
    }

    .login-kicker {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--green);
        font-size: .72rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .login-kicker::before {
        content: "";
        width: 30px;
        height: 2px;
        background: var(--green);
    }

    .login-copy h1 {
        margin: 18px 0;
        color: #000;
        font-size: clamp(2.3rem, 4vw, 4.15rem);
        font-weight: 600;
        line-height: 1.04;
    }

    .login-copy h1 span { color: var(--green); }

    .login-copy p {
        max-width: 460px;
        color: #000;
        font-size: 1rem;
        line-height: 1.7;
    }

    .login-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .login-meta span {
        border: 1px solid #dfe5e1;
        border-radius: 8px;
        padding: 7px 10px;
        color: #000;
        background: #fff;
        font-size: .68rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .login-system-art {
        display: none;
    }

    .login-system-art .orbit {
        transform-origin: 280px 280px;
        animation: login-orbit 42s linear infinite;
    }

    .login-system-art .node { animation: login-pulse 2.8s ease-in-out infinite; }
    .login-system-art .node:nth-child(2n) { animation-delay: .9s; }

    @keyframes login-orbit { to { transform: rotate(360deg); } }
    @keyframes login-pulse { 50% { opacity: .28; } }

    .login-auth-panel {
        position: relative;
        display: grid;
        min-height: 100vh;
        place-items: center;
        padding: clamp(24px, 4vw, 54px);
        background:
            radial-gradient(circle at 30% 10%, rgba(0, 166, 81, .08), transparent 30%),
            #F7F8F5;
    }

    .login-auth-wrap {
        width: min(100%, 540px);
    }

    .login-auth-label {
        color: var(--green);
        font-size: .72rem;
        font-weight: 500;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .login-auth-wrap > h2 {
        margin-bottom: 8px;
        font-size: clamp(2rem, 3.4vw, 3rem);
        font-weight: 600;
        line-height: 1.05;
        color: #000;
    }

    .login-auth-intro {
        margin-bottom: 22px;
        color: #000;
        line-height: 1.6;
    }

    .login-card {
        padding: 22px;
        border: 1px solid #dfe5e1;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 16px 44px rgba(15, 23, 42, .07);
    }

    .login-card .nav-pills {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 4px;
        padding: 4px;
        margin-bottom: 18px !important;
        border-radius: 8px;
        background: #EAF8F0;
    }

    .login-card .nav-item { display: grid; }

    .login-card .nav-link {
        border-radius: 7px;
        color: #000;
        padding: .62rem .45rem;
        font-size: .82rem;
        font-weight: 500;
    }

    .login-card .nav-link.active {
        background: var(--green);
        color: #fff;
        box-shadow: 0 6px 14px rgba(0, 166, 81, .18);
    }

    .login-card .form-label {
        margin-bottom: 7px;
        color: #000;
        font-size: .78rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .login-card .form-control {
        min-height: 46px;
        border-color: #d7ddd9;
        border-radius: 8px;
        background: #fff;
        color: #000;
    }

    .login-card .form-control:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 .2rem rgba(0, 166, 81, .16);
    }

    .login-card a {
        color: var(--green);
        font-weight: 700;
    }

    .login-card .btn-warning {
        min-height: 48px;
        border: 0;
        border-radius: 8px;
        background: var(--green);
        color: #fff;
        font-size: .9rem;
        font-weight: 900;
        box-shadow: 0 12px 26px rgba(0, 166, 81, .22);
    }

    .login-card .btn-warning:hover,
    .login-card .btn-warning:focus {
        background: #008F45;
        color: #fff;
    }

    .password-wrap { position: relative; }
    .password-wrap .form-control { padding-right: 46px; }

    .password-toggle {
        position: absolute;
        right: 7px;
        top: 5px;
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #67706c;
    }

    .password-toggle:hover {
        background: #eef3f0;
        color: #000;
    }

    .auth-security-note {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-top: 16px;
        color: #000;
        font-size: .78rem;
    }

    .auth-security-note i { color: var(--green); }
    .login-mobile-brand { display: none; }

    @media (max-width: 980px) {
        .login-stage {
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
        }

        .login-brand-panel {
            min-height: auto;
            padding: 24px 34px;
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .login-copy {
            margin-top: 28px;
            max-width: 680px;
        }

        .login-copy h1 {
            max-width: 650px;
            margin: 12px 0;
            font-size: 2.4rem;
        }

        .login-copy h1 br:nth-of-type(2) { display: none; }
        .login-copy p { max-width: 610px; font-size: .92rem; }
        .login-meta { margin-top: 24px; }
        .login-system-art { width: 360px; right: -100px; opacity: .34; }
        .login-auth-panel { min-height: auto; padding: 34px 24px; }
    }

    @media (max-width: 680px) {
        .login-stage {
            display: block;
            min-height: 100vh;
            background: #F7F8F5;
        }

        .login-brand-panel { display: none; }

        .login-auth-panel {
            min-height: 100vh;
            align-items: start;
            padding: 28px 16px;
        }

        .login-mobile-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 26px;
            font-weight: 900;
        }

        .login-auth-intro { margin-bottom: 18px; }
        .login-card { padding: 18px 15px; }
        .login-card .d-flex.justify-content-between { gap: 12px; font-size: .86rem; }
    }

    @media (max-width: 380px) {
        .login-auth-panel { padding-inline: 10px; }
        .login-card .nav-link { font-size: .7rem; padding-inline: .2rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-system-art .orbit,
        .login-system-art .node { animation: none; }
    }

    html[data-theme="dark"] body:has(.login-stage),
    html[data-theme="dark"] .login-stage,
    html[data-theme="dark"] .login-auth-panel {
        background: #F7F8F5 !important;
        color: #000 !important;
    }

    html[data-theme="dark"] .login-brand-panel,
    html[data-theme="dark"] .login-card {
        background-color: #fff !important;
        border-color: #e5e7eb !important;
        color: #000 !important;
        box-shadow: 0 16px 44px rgba(15, 23, 42, .07) !important;
    }

    html[data-theme="dark"] .login-copy h1,
    html[data-theme="dark"] .login-auth-wrap > h2,
    html[data-theme="dark"] .login-copy p,
    html[data-theme="dark"] .login-auth-label,
    html[data-theme="dark"] .login-auth-intro,
    html[data-theme="dark"] .login-card .form-label,
    html[data-theme="dark"] .login-card .form-check-label,
    html[data-theme="dark"] .login-card .text-muted,
    html[data-theme="dark"] .auth-security-note,
    html[data-theme="dark"] .login-mobile-brand,
    html[data-theme="dark"] .login-meta span {
        color: #000 !important;
    }

    html[data-theme="dark"] .login-card .nav-pills {
        background: #EAF8F0 !important;
    }

    html[data-theme="dark"] .login-card .nav-pills .nav-link {
        color: #000 !important;
    }

    html[data-theme="dark"] .login-card .nav-pills .nav-link.active {
        background: #00A651 !important;
        color: #fff !important;
    }

    html[data-theme="dark"] .login-card .form-control {
        background: #fff !important;
        border-color: #d7ddd9 !important;
        color: #000 !important;
    }
</style>

<div class="login-stage">
    <section class="login-brand-panel" aria-label="BAMA connected systems">
        <div>
            <a href="{{ route('landing') }}" class="login-brand" aria-label="Back to BAMA home">
                <img src="{{ $brandLogoUrl }}" alt="Bama Solutions" class="brand-mark">
            </a>
            <a href="{{ route('landing') }}" class="login-home-link"><i class="bi bi-arrow-left"></i> Back home</a>
        </div>

        <div class="login-copy">
            <div class="login-kicker">Secure operations platform</div>
            <h1>Engineering<br>the systems<br><span>behind ambition.</span></h1>
            <p>One dependable workspace connecting commercial operations, finance, projects and organisational intelligence.</p>
        </div>

        <div class="login-meta">
            <span>Encrypted access</span>
            <span>Role controlled</span>
            <span>Activity audited</span>
            <span>Built to scale</span>
        </div>

        <svg class="login-system-art" viewBox="0 0 560 560" aria-hidden="true">
            <g fill="none">
                <circle cx="280" cy="280" r="204" stroke="#fff" stroke-opacity=".09"/>
                <circle cx="280" cy="280" r="148" stroke="#00A651" stroke-opacity=".26" stroke-dasharray="4 10"/>
                <g class="orbit">
                    <ellipse cx="280" cy="280" rx="246" ry="105" stroke="#fff" stroke-opacity=".12"/>
                    <ellipse cx="280" cy="280" rx="105" ry="246" stroke="#00A651" stroke-opacity=".2" stroke-dasharray="7 12"/>
                </g>
                <g stroke="#00A651" stroke-opacity=".45" stroke-dasharray="3 9">
                    <path d="M55 280h145M360 280h145M280 55v145M280 360v145"/>
                </g>
            </g>
            <circle cx="280" cy="280" r="70" fill="#000" stroke="#00A651"/>
            <path d="M250 255h60M250 280h40M250 305h50" stroke="#00A651" stroke-width="3" stroke-linecap="round"/>
            <g class="node" fill="#000" stroke="#00A651">
                <circle cx="55" cy="280" r="9"/>
                <circle cx="505" cy="280" r="9"/>
                <circle cx="280" cy="55" r="9"/>
                <circle cx="280" cy="505" r="9"/>
            </g>
        </svg>
    </section>

    <section class="login-auth-panel">
        <div class="login-auth-wrap">
            <a href="{{ route('landing') }}" class="login-mobile-brand" aria-label="Back to BAMA home">
                <img src="{{ $brandLogoUrl }}" alt="Bama Solutions" class="brand-mark">
            </a>

            <div class="login-auth-label">{{ $loginCopy['label'] }}</div>
            <h2>{{ $loginCopy['title'] }}</h2>
            <p class="login-auth-intro">{{ $loginCopy['intro'] }}</p>

            <div class="login-card">
                @if ($otpAvailable)
                    <ul class="nav nav-pills" role="tablist">
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
                        <label class="form-label">Username or email</label>
                        <input name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="password-wrap">
                            <input id="login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember">
                            <span class="form-check-label">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                    <button class="btn btn-warning w-100">Sign in securely <i class="bi bi-arrow-right ms-1"></i></button>
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
                                    <small class="text-muted d-block mb-2">Didn’t receive it? Check your spam folder.</small>
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

                <div class="auth-security-note">
                    <i class="bi bi-shield-check"></i>
                    <span>Your access is encrypted, role-controlled and recorded for security.</span>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const password = document.querySelector('#login-password');
        const toggle = document.querySelector('.password-toggle');

        if (password && toggle) {
            toggle.addEventListener('click', () => {
                const show = password.type === 'password';
                password.type = show ? 'text' : 'password';
                toggle.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        }

        @if ($otpSent)
            const button = document.querySelector('#resend-otp');
            const countdown = document.querySelector('#otp-countdown');
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
        @endif
    });
</script>
@endsection
