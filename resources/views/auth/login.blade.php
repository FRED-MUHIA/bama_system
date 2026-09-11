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
        min-height: 100vh;
        min-height: 100dvh;
    }

    .website-auth-panel {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .08);
    }

    .website-auth-tabs {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 4px;
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

    .website-auth-panel .form-label {
        display: block;
        margin-bottom: .45rem;
        color: #000;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .website-auth-panel a {
        color: #007A3B;
        font-weight: 800;
    }

    .website-auth-submit {
        min-height: 50px;
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

    .password-wrap { position: relative; }
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
        color: #52525b;
    }

    .website-auth-pane[hidden] { display: none; }

    @media (max-width: 767.98px) {
        .website-auth-panel {
            border: 0;
            box-shadow: none;
        }
    }
</style>

<main class="website-auth">
    <div class="website-auth-shell mx-auto grid max-w-7xl gap-8 px-5 py-6 lg:grid-cols-[.72fr_1fr] lg:px-8 lg:py-8">
        <aside class="hidden min-h-[calc(100vh-4rem)] flex-col justify-between border-r border-zinc-200 bg-white px-8 py-7 lg:flex">
            <a href="{{ route('landing') }}" aria-label="Back to Bama home">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="website-auth-logo">
            </a>
            <div>
                <p class="text-xs font-semibold uppercase text-[#00A651]">{{ $loginCopy['label'] }}</p>
                <h1 class="mt-4 max-w-sm text-4xl font-black leading-tight">Access your Bama workspace.</h1>
                <p class="mt-4 max-w-md text-base leading-7 text-black">{{ $loginCopy['intro'] }}</p>
            </div>
            <div class="grid gap-3 text-sm text-zinc-600">
                <span><i class="bi bi-shield-check text-[#00A651]"></i> Encrypted access</span>
                <span><i class="bi bi-person-lock text-[#00A651]"></i> Role controlled</span>
                <span><i class="bi bi-clock-history text-[#00A651]"></i> Activity audited</span>
            </div>
        </aside>

        <section class="flex min-h-[calc(100vh-3rem)] items-center justify-center">
            <div class="w-full max-w-[440px]">
                <div class="mb-5 flex items-center justify-between gap-3 lg:hidden">
                    <a href="{{ route('landing') }}" aria-label="Back to Bama home">
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="website-auth-logo">
                    </a>
                </div>

                <div class="mb-5">
                    <p class="text-xs font-bold uppercase text-[#00A651]">{{ $loginCopy['label'] }}</p>
                    <h2 class="mt-2 text-3xl font-black">{{ $loginCopy['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $loginCopy['intro'] }}</p>
                </div>

                <div class="website-auth-panel p-5 sm:p-6">
                    @if ($otpAvailable)
                        <div class="website-auth-tabs mb-5" role="tablist">
                            <button class="{{ $otpSent ? '' : 'active' }}" type="button" data-website-auth-tab="password-login">Password</button>
                            <button class="{{ $otpSent ? 'active' : '' }}" type="button" data-website-auth-tab="otp-login">OTP</button>
                            <button type="button" data-website-auth-tab="magic-login">Magic link</button>
                        </div>
                    @endif

                    <div id="password-login" class="website-auth-pane" @if($otpAvailable && $otpSent) hidden @endif>
                        <form method="post" action="{{ $loginActions['password'] }}" class="grid gap-4">
                            @csrf
                            <input type="hidden" name="login_context" value="{{ $loginContext }}">
                            <label>
                                <span class="form-label">Email or Username</span>
                                <input name="username" value="{{ old('username') }}" class="form-control" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                            </label>
                            <label>
                                <span class="form-label">Password</span>
                                <span class="password-wrap block">
                                    <input id="login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="login-password"><i class="bi bi-eye"></i></button>
                                </span>
                            </label>
                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                <label class="inline-flex items-center gap-2 text-zinc-600">
                                    <input class="rounded border-zinc-300" type="checkbox" name="remember">
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
                                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                                    <strong>OTP sent</strong><br>
                                    <small>We sent a 6-digit code to {{ session('otp_email') }}.</small>
                                </div>
                                <form method="post" action="{{ $loginActions['otpVerify'] }}" class="grid gap-4">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                    <label>
                                        <span class="form-label">Verification code</span>
                                        <input class="form-control text-center text-xl" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                                    </label>
                                    <button class="website-auth-submit">Verify OTP</button>
                                </form>
                                <form method="post" action="{{ $loginActions['otpRequest'] }}" class="mt-3 text-center">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                    <button id="resend-otp" class="text-sm font-bold text-[#007A3B]" disabled data-ready-at="{{ session('otp_resend_at') }}">Resend OTP in <span id="otp-countdown">60</span>s</button>
                                </form>
                            @else
                                <form method="post" action="{{ $loginActions['otpRequest'] }}" class="grid gap-4">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <label>
                                        <span class="form-label">Work email</span>
                                        <input class="form-control" name="email" type="email" value="{{ old('email') }}" required>
                                    </label>
                                    <button class="website-auth-submit">Send one-time code</button>
                                </form>
                            @endif
                        </div>

                        <div id="magic-login" class="website-auth-pane" hidden>
                            <form method="post" action="{{ $loginActions['magic'] }}" class="grid gap-4">
                                @csrf
                                <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                <label>
                                    <span class="form-label">Work email</span>
                                    <input class="form-control" name="email" type="email" required>
                                </label>
                                <button class="website-auth-submit">Email secure login link</button>
                            </form>
                        </div>
                    @endif

                    @if($loginContext === 'business')
                        <div class="mt-5 text-center text-sm text-zinc-600">
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
