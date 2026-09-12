@extends('layouts.marketing', [
    'title' => 'Secure Access',
])

@section('body')
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

    $homeSections = \App\Models\MarketingPage::resolve('home')->sections ?? \App\Models\MarketingPage::defaultSections('home');
    $brand = array_replace_recursive(\App\Models\MarketingPage::defaultSections('home')['brand'], (array) data_get($homeSections, 'brand', []));
    $brandLogoUrl = \App\Support\PublicUpload::url(data_get($brand, 'logo_path')) ?: \App\Support\PublicUpload::url('logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png') ?: asset('images/bama-solutions-02.png');
    $brandAlt = data_get($brand, 'logo_alt', 'Bama Solutions');
@endphp

<style>
    .website-auth {
        min-height: 100vh;
        min-height: 100dvh;
        display: grid;
        align-items: center;
        padding: clamp(1.25rem, 3vw, 2.5rem);
        background: #F7F8F5;
        color: #000;
    }

    .website-auth * { letter-spacing: 0; }

    .website-auth-logo {
        display: block;
        width: 160px;
        max-width: 44vw;
        height: auto;
        object-fit: contain;
    }

    .website-auth-shell {
        width: min(100%, 1180px);
        min-height: min(760px, calc(100dvh - 5rem));
        display: grid;
        grid-template-columns: minmax(320px, .84fr) minmax(360px, 1fr);
        align-items: stretch;
        gap: clamp(1.5rem, 3vw, 3rem);
        margin: 0 auto;
    }

    .website-auth-hero {
        min-width: 0;
        min-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 2.5rem;
        padding: clamp(2rem, 4vw, 3rem);
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .06);
    }

    .website-auth-hero-main {
        display: grid;
        gap: 1rem;
    }

    .website-auth-kicker {
        margin: 0;
        color: #00A651;
        font-size: .75rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .website-auth-hero-title {
        max-width: 420px;
        margin: 0;
        color: #000;
        font-size: clamp(2.2rem, 3.5vw, 3.4rem);
        font-weight: 800 !important;
        line-height: 1.08;
    }

    .website-auth-hero-copy {
        max-width: 440px;
        margin: 0;
        color: #111827;
        font-size: 1.04rem;
        line-height: 1.7;
    }

    .website-auth-feature-list {
        display: grid;
        gap: .85rem;
        margin: 0;
        padding: 0;
        color: #475467;
        font-size: .95rem;
        list-style: none;
    }

    .website-auth-feature-list li {
        display: flex;
        align-items: center;
        gap: .6rem;
        min-width: 0;
    }

    .website-auth-feature-list i {
        width: 1.25rem;
        color: #00A651;
        text-align: center;
        flex: 0 0 1.25rem;
    }

    .website-auth-form-section {
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .website-auth-form-wrap {
        width: min(100%, 440px);
        min-width: 0;
        display: grid;
        gap: 1.25rem;
    }

    .website-auth-mobile-brand {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .website-auth-heading {
        display: grid;
        gap: .55rem;
        min-width: 0;
    }

    .website-auth-heading h2 {
        margin: 0;
        color: #000;
        font-size: clamp(2rem, 4vw, 2.65rem);
        font-weight: 800 !important;
        line-height: 1.08;
    }

    .website-auth-heading p {
        margin: 0;
        color: #475467;
        font-size: .98rem;
        line-height: 1.6;
    }

    .website-auth-panel {
        width: 100%;
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .08);
    }

    .website-auth-panel label {
        display: block;
        min-width: 0;
    }

    .website-auth-form {
        display: grid;
        gap: 1rem;
    }

    .website-auth-tabs {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 4px;
        margin-bottom: 1.25rem;
        padding: 4px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f4f6f3;
    }

    .website-auth-tabs button {
        min-height: 42px;
        border: 0;
        border-radius: 8px;
        color: #52525b;
        font-size: .82rem;
        font-weight: 800;
    }

    .website-auth-tabs button.active {
        background: #00A651;
        color: #fff;
    }

    .website-auth-panel .form-control,
    .website-auth-panel .field-control {
        display: block;
        width: 100%;
        min-height: 50px;
        border: 1px solid #d4d4d8;
        border-radius: 10px;
        background: #fff;
        color: #000;
        padding: .85rem 1rem;
        font-size: 16px;
    }

    .website-auth-panel .form-control:focus,
    .website-auth-panel .field-control:focus {
        border-color: #00A651;
        outline: 0;
        box-shadow: 0 0 0 4px rgba(0, 166, 81, .12);
    }

    .website-auth-panel .website-auth-code {
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0;
    }

    .website-auth-panel .form-label {
        display: block;
        margin-bottom: .45rem;
        color: #000;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .website-auth-links {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        color: #52525b;
        font-size: .9rem;
    }

    .website-auth-remember {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        min-height: 2.25rem;
        margin: 0;
        color: #52525b;
    }

    .website-auth-remember input {
        width: 1rem;
        height: 1rem;
        accent-color: #00A651;
    }

    .website-auth-panel a {
        color: #007A3B;
        font-weight: 800;
    }

    .website-auth-submit {
        width: 100%;
        min-height: 50px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 10px;
        background: #00A651;
        color: #fff;
        font-weight: 900;
    }

    .website-auth-submit:hover,
    .website-auth-submit:focus {
        background: #007A3B;
        color: #fff;
    }

    .website-auth-register {
        margin-top: 1.25rem;
        color: #52525b;
        font-size: .9rem;
        line-height: 1.5;
        text-align: center;
    }

    .website-auth-otp-success {
        margin-bottom: 1rem;
        padding: .85rem;
        border: 1px solid #BDE8CF;
        border-radius: 10px;
        background: #EAF8F0;
        color: #007A3B;
        font-size: .9rem;
        line-height: 1.45;
    }

    .website-auth-resend-form {
        margin-top: 1rem;
        text-align: center;
    }

    .website-auth-resend {
        min-height: 2.25rem;
        border: 0;
        background: transparent;
        color: #007A3B;
        font-size: .9rem;
        font-weight: 800;
    }

    .password-wrap {
        position: relative;
        display: block;
    }
    .password-wrap .form-control { padding-right: 3.25rem; }
    .password-toggle {
        position: absolute;
        top: 50%;
        right: .35rem;
        width: 42px;
        height: 42px;
        transform: translateY(-50%);
        border: 0;
        border-radius: 8px;
        background: #f4f6f3;
        color: #52525b;
    }

    .password-toggle:hover,
    .password-toggle:focus {
        color: #007A3B;
    }

    .website-auth-pane[hidden] { display: none; }

    @media (max-width: 991.98px) {
        .website-auth {
            align-items: start;
            padding: 0;
        }

        .website-auth-shell {
            width: 100%;
            min-height: 100dvh;
            display: block;
            padding: clamp(1.25rem, 5vw, 2rem);
        }

        .website-auth-hero {
            display: none;
        }

        .website-auth-form-section {
            min-height: calc(100dvh - clamp(2.5rem, 10vw, 4rem));
            align-items: start;
            padding-top: .5rem;
        }

        .website-auth-form-wrap {
            width: min(100%, 440px);
            margin: 0 auto;
        }

        .website-auth-mobile-brand {
            display: flex;
        }
    }

    @media (max-width: 575.98px) {
        .website-auth-panel {
            padding: 1.1rem;
            border-radius: 14px;
            box-shadow: none;
        }

        .website-auth-links {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<main class="website-auth">
    <div class="website-auth-shell">
        <aside class="website-auth-hero">
            <a href="{{ route('landing') }}" aria-label="Back to Bama home">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="website-auth-logo">
            </a>
            <div class="website-auth-hero-main">
                <p class="website-auth-kicker">{{ $loginCopy['label'] }}</p>
                <h1 class="website-auth-hero-title">Access your Bama workspace.</h1>
                <p class="website-auth-hero-copy">{{ $loginCopy['intro'] }}</p>
            </div>
            <ul class="website-auth-feature-list">
                <li><i class="bi bi-shield-check"></i> Encrypted access</li>
                <li><i class="bi bi-person-lock"></i> Role controlled</li>
                <li><i class="bi bi-clock-history"></i> Activity audited</li>
            </ul>
        </aside>

        <section class="website-auth-form-section" aria-label="{{ $loginCopy['title'] }}">
            <div class="website-auth-form-wrap">
                <div class="website-auth-mobile-brand">
                    <a href="{{ route('landing') }}" aria-label="Back to Bama home">
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="website-auth-logo">
                    </a>
                </div>

                <header class="website-auth-heading">
                    <p class="website-auth-kicker">{{ $loginCopy['label'] }}</p>
                    <h2>{{ $loginCopy['title'] }}</h2>
                    <p>{{ $loginCopy['intro'] }}</p>
                </header>

                <div class="website-auth-panel">
                    @if ($otpAvailable)
                        <div class="website-auth-tabs" role="tablist" aria-label="Sign in method">
                            <button class="{{ $otpSent ? '' : 'active' }}" type="button" data-website-auth-tab="password-login">Password</button>
                            <button class="{{ $otpSent ? 'active' : '' }}" type="button" data-website-auth-tab="otp-login">OTP</button>
                            <button type="button" data-website-auth-tab="magic-login">Magic link</button>
                        </div>
                    @endif

                    <div id="password-login" class="website-auth-pane" @if($otpAvailable && $otpSent) hidden @endif>
                        <form method="post" action="{{ $loginActions['password'] }}" class="website-auth-form">
                            @csrf
                            <input type="hidden" name="login_context" value="{{ $loginContext }}">
                            <label>
                                <span class="form-label">Email or Username</span>
                                <input name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                            </label>
                            <label>
                                <span class="form-label">Password</span>
                                <span class="password-wrap">
                                    <input id="login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="login-password"><i class="bi bi-eye"></i></button>
                                </span>
                            </label>
                            <div class="website-auth-links">
                                <label class="website-auth-remember">
                                    <input type="checkbox" name="remember">
                                    <span>Remember me</span>
                                </label>
                                <a href="{{ route('password.request') }}">Forgot password?</a>
                            </div>
                            <button class="website-auth-submit" type="submit">Sign in</button>
                        </form>
                    </div>

                    @if ($otpAvailable)
                        <div id="otp-login" class="website-auth-pane" @unless($otpSent) hidden @endunless>
                            @if ($otpSent)
                                <div class="website-auth-otp-success">
                                    <strong>OTP sent</strong><br>
                                    <small>We sent a 6-digit code to {{ session('otp_email') }}.</small>
                                </div>
                                <form method="post" action="{{ $loginActions['otpVerify'] }}" class="website-auth-form">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                    <label>
                                        <span class="form-label">Verification code</span>
                                        <input class="form-control website-auth-code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                                    </label>
                                    <button class="website-auth-submit" type="submit">Verify OTP</button>
                                </form>
                                <form method="post" action="{{ $loginActions['otpRequest'] }}" class="website-auth-resend-form">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                    <button id="resend-otp" class="website-auth-resend" type="submit" disabled data-ready-at="{{ session('otp_resend_at') }}">Resend OTP in <span id="otp-countdown">60</span>s</button>
                                </form>
                            @else
                                <form method="post" action="{{ $loginActions['otpRequest'] }}" class="website-auth-form">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <label>
                                        <span class="form-label">Work email</span>
                                        <input class="form-control" name="email" type="email" value="{{ old('email') }}" required>
                                    </label>
                                    <button class="website-auth-submit" type="submit">Send one-time code</button>
                                </form>
                            @endif
                        </div>

                        <div id="magic-login" class="website-auth-pane" hidden>
                            <form method="post" action="{{ $loginActions['magic'] }}" class="website-auth-form">
                                @csrf
                                <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                <label>
                                    <span class="form-label">Work email</span>
                                    <input class="form-control" name="email" type="email" required>
                                </label>
                                <button class="website-auth-submit" type="submit">Email secure login link</button>
                            </form>
                        </div>
                    @endif

                    @if($loginContext === 'business')
                        <div class="website-auth-register">
                            Do not have an account? <a href="{{ route('register.account') }}">Create Account</a>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-website-auth-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('[data-website-auth-tab]').forEach((item) => {
                    item.classList.toggle('active', item === tab);
                });

                document.querySelectorAll('.website-auth-pane').forEach((pane) => {
                    pane.hidden = pane.id !== tab.dataset.websiteAuthTab;
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
@endsection
