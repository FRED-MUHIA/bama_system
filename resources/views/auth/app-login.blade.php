@extends('layouts.app')
@section('title','Bama App')
@section('content')
@php
    $otpAvailable = (bool) ($otpAvailable ?? false);
    $otpSent = (bool) session('otp_sent');
    $loginContext = $loginContext ?? session('otp_context', 'business');
    $publicLoginPrefix = request()->routeIs('public.*') ? 'public.' : '';
    $loginActions = [
        'password' => route($publicLoginPrefix.'login.store'),
        'otpRequest' => route($publicLoginPrefix.'login.otp.request'),
        'otpVerify' => route($publicLoginPrefix.'login.otp.verify'),
        'magic' => route($publicLoginPrefix.'login.magic.request'),
    ];
    $system = $loginSystem ?? ['workspaces' => 'Ready', 'modules' => 'Live', 'industries' => 'Many', 'security' => 'Encrypted'];
    $initialStep = ($errors->any() || $otpSent) ? 2 : 0;
    $brandLogoPath = 'images/bama-solutions-02.png';
    $brandLogoUrl = asset($brandLogoPath).'?v='.(file_exists(public_path($brandLogoPath)) ? filemtime(public_path($brandLogoPath)) : time());
    $heroImagePath = 'images/analytics-command-center.png';
    $heroImageUrl = asset($heroImagePath).'?v='.(file_exists(public_path($heroImagePath)) ? filemtime(public_path($heroImagePath)) : time());
@endphp

<style>
    body:has(.app-flow) { padding-bottom:0 !important; background:#061512 !important; overscroll-behavior:none; }
    main > section:has(.app-flow) { max-width:none; padding:0 !important; overflow:hidden; }
    main > section:has(.app-flow) > .alert {
        position:fixed;
        top:calc(10px + env(safe-area-inset-top));
        left:50%;
        z-index:60;
        width:min(430px, calc(100% - 22px));
        transform:translateX(-50%);
        border-radius:8px;
        box-shadow:0 18px 42px rgba(0,0,0,.24);
    }
    body:has(.app-flow) .guest-theme-toggle { display:none !important; }

    .app-flow {
        --step:0;
        --teal:#31c6bb;
        --teal-dark:#0fa899;
        --night:#061512;
        position:relative;
        width:100vw;
        height:100vh;
        height:100dvh;
        overflow:hidden;
        color:#fff;
        background:#061512;
        touch-action:pan-y;
    }
    .app-flow * { letter-spacing:0; }
    .app-flow-track {
        display:flex;
        width:300vw;
        height:100%;
        transform:translateX(calc(var(--step) * -100vw));
        transition:transform .38s cubic-bezier(.2,.78,.18,1);
    }
    .app-screen {
        position:relative;
        flex:0 0 100vw;
        width:100vw;
        min-height:100%;
        padding:calc(28px + env(safe-area-inset-top)) clamp(22px,6vw,42px) calc(28px + env(safe-area-inset-bottom));
        overflow:hidden auto;
        background:
            linear-gradient(180deg,rgba(4,22,18,.28),rgba(4,14,12,.97)),
            linear-gradient(90deg,rgba(3,20,16,.9),rgba(3,20,16,.38) 62%,rgba(3,20,16,.9)),
            url("{{ $heroImageUrl }}") center/cover no-repeat;
        -webkit-overflow-scrolling:touch;
    }
    .app-screen::after {
        content:"";
        position:absolute;
        inset:0;
        background:
            radial-gradient(circle at 76% 10%,rgba(49,198,187,.35),transparent 28%),
            linear-gradient(180deg,rgba(49,198,187,.08),transparent 30%);
        pointer-events:none;
    }
    .app-screen > * { position:relative; z-index:1; }
    .app-screen-center {
        min-height:calc(100dvh - 56px - env(safe-area-inset-top) - env(safe-area-inset-bottom));
        display:flex;
        flex-direction:column;
    }
    .app-logo {
        display:flex;
        justify-content:center;
        margin-bottom:auto;
    }
    .app-logo img {
        width:clamp(142px,38vw,210px);
        height:auto;
        object-fit:contain;
        filter:drop-shadow(0 14px 28px rgba(0,0,0,.2));
    }
    .app-kicker {
        display:flex;
        align-items:center;
        gap:14px;
        color:rgba(255,255,255,.88);
        font-size:.78rem;
        font-weight:900;
        text-transform:uppercase;
    }
    .app-kicker::before {
        content:"";
        width:52px;
        height:4px;
        border-radius:999px;
        background:var(--teal);
        box-shadow:0 0 18px rgba(49,198,187,.5);
    }
    .app-title {
        margin:28px 0 0;
        max-width:690px;
        color:#fff;
        font-size:clamp(4.1rem,17vw,8rem);
        font-weight:850 !important;
        line-height:.95;
        text-shadow:0 18px 45px rgba(0,0,0,.32);
    }
    .app-copy {
        max-width:540px;
        margin:28px 0 0;
        color:rgba(255,255,255,.76);
        font-size:clamp(1.02rem,4.6vw,1.45rem);
        line-height:1.42;
    }
    .app-primary,
    .app-secondary {
        width:100%;
        min-height:64px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:12px;
        border-radius:999px;
        border:0;
        text-decoration:none;
        font-size:1.04rem;
        font-weight:950;
    }
    .app-primary {
        margin-top:auto;
        background:linear-gradient(135deg,#42d5ca,#0fa899);
        color:#fff;
        box-shadow:0 18px 44px rgba(17,170,156,.35);
    }
    .app-primary:hover { color:#fff; filter:brightness(1.04); }
    .app-secondary {
        background:#fff;
        color:#061512 !important;
    }
    .app-secondary:hover { color:#061512 !important; background:#f6fbfa; }

    .app-choice-card,
    .app-auth-card {
        width:min(100%,560px);
        margin-inline:auto;
        border:1px solid rgba(255,255,255,.09);
        background:rgba(2,15,13,.92);
        box-shadow:0 30px 70px rgba(0,0,0,.3);
        backdrop-filter:blur(18px);
    }
    .app-choice-card {
        margin-top:auto;
        padding:28px 22px;
        border-radius:32px 32px 8px 8px;
    }
    .app-choice-card h2,
    .app-auth-card h2 {
        margin:0 0 10px;
        color:#fff;
        font-size:clamp(2.1rem,8vw,3.4rem);
        font-weight:850 !important;
        line-height:1.02;
    }
    .app-choice-card p {
        margin:0 0 26px;
        color:rgba(255,255,255,.72);
        font-size:1rem;
        line-height:1.55;
    }
    .app-choice-actions {
        display:grid;
        gap:14px;
    }
    .app-stat-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
        margin:22px 0 0;
    }
    .app-stat-grid span {
        min-height:68px;
        display:flex;
        flex-direction:column;
        justify-content:center;
        border:1px solid rgba(255,255,255,.1);
        border-radius:8px;
        padding:10px 12px;
        background:rgba(255,255,255,.045);
    }
    .app-stat-grid strong {
        color:#fff;
        font-size:1.2rem;
        font-weight:950;
    }
    .app-stat-grid small {
        color:rgba(255,255,255,.68);
        font-size:.64rem;
        font-weight:900;
        text-transform:uppercase;
    }

    .app-auth-top {
        width:min(100%,560px);
        margin:0 auto 14px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
    }
    .app-icon-button {
        width:46px;
        height:46px;
        display:grid;
        place-items:center;
        border:0;
        border-radius:50%;
        background:rgba(255,255,255,.1);
        color:#fff;
    }
    .app-auth-card {
        padding:22px;
        border-radius:28px 28px 8px 8px;
    }
    .app-tabs {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:5px;
        margin:0 0 20px !important;
        padding:5px;
        border:1px solid rgba(255,255,255,.08);
        border-radius:999px;
        background:rgba(255,255,255,.05);
    }
    .app-tabs .nav-item { display:grid; }
    .app-tabs .nav-link {
        min-height:42px;
        border-radius:999px;
        color:rgba(255,255,255,.72);
        font-size:.72rem;
        font-weight:950;
        padding:.42rem .32rem;
        white-space:nowrap;
    }
    .app-tabs .nav-link.active {
        background:#00A651;
        color:#fff;
    }
    .app-auth-card .form-label {
        margin-bottom:10px;
        color:#fff;
        font-size:.86rem;
        font-weight:950;
        text-transform:uppercase;
    }
    .app-auth-card .form-control {
        min-height:62px;
        border:1px solid rgba(255,255,255,.16);
        border-radius:24px;
        background:rgba(255,255,255,.11);
        color:#fff;
        font-size:1rem;
        padding:1rem 1.18rem;
        box-shadow:inset 0 0 0 1px rgba(255,255,255,.04);
    }
    .app-auth-card .form-control:focus {
        border-color:var(--teal);
        background:rgba(255,255,255,.14);
        color:#fff;
        box-shadow:0 0 0 .22rem rgba(49,198,187,.18);
    }
    .app-auth-card .form-check-input {
        border-color:rgba(255,255,255,.4);
        background-color:rgba(255,255,255,.08);
    }
    .app-auth-card .form-check-input:checked {
        background-color:var(--teal);
        border-color:var(--teal);
    }
    .app-auth-card .form-check-label,
    .app-auth-card .text-muted {
        color:rgba(255,255,255,.72) !important;
    }
    .app-auth-card a {
        color:#dffdfa;
        font-weight:950;
        text-decoration-thickness:2px;
        text-underline-offset:4px;
    }
    .app-auth-card .btn-warning {
        min-height:62px;
        border:0;
        border-radius:999px;
        background:linear-gradient(135deg,#42d5ca,#0fa899);
        color:#fff;
        font-size:1.02rem;
        font-weight:950;
        box-shadow:0 18px 44px rgba(17,170,156,.28);
    }
    .app-auth-card .btn-warning:hover,
    .app-auth-card .btn-warning:focus {
        background:linear-gradient(135deg,#48ddd2,#0fa899);
        color:#fff;
    }
    .app-register-link {
        min-height:60px;
        border-radius:999px;
        border:0;
        background:#fff;
        color:#061512 !important;
        font-weight:950;
        text-decoration:none !important;
    }
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
    .app-security {
        display:flex;
        align-items:flex-start;
        gap:9px;
        margin-top:18px;
        color:rgba(255,255,255,.66);
        font-size:.78rem;
        line-height:1.45;
    }
    .app-security i { color:var(--teal); margin-top:1px; }
    .app-legal {
        width:min(100%,520px);
        margin:22px auto 0;
        color:rgba(255,255,255,.62);
        font-size:.76rem;
        line-height:1.5;
        text-align:center;
    }
    .otp-success {
        border-color:rgba(49,198,187,.32);
        background:rgba(49,198,187,.12);
        color:#dffdfa;
    }
    .app-dots {
        position:fixed;
        left:50%;
        bottom:calc(12px + env(safe-area-inset-bottom));
        z-index:20;
        display:flex;
        gap:7px;
        transform:translateX(-50%);
    }
    .app-dot {
        width:7px;
        height:7px;
        border:0;
        border-radius:99px;
        background:rgba(255,255,255,.34);
    }
    .app-dot.active {
        width:22px;
        background:var(--teal);
    }

    @media (min-width:768px) {
        .app-screen {
            display:grid;
            place-items:center;
            padding-inline:48px;
        }
        .app-screen-center {
            width:min(100%,680px);
            min-height:min(820px, calc(100dvh - 80px));
        }
    }
    @media (max-width:380px) {
        .app-screen { padding-inline:16px; }
        .app-title { font-size:3.65rem; }
        .app-auth-card { padding-inline:14px; }
        .app-tabs { border-radius:18px; }
        .app-tabs .nav-link { white-space:normal; line-height:1.05; }
    }
    @media (prefers-reduced-motion:reduce) {
        .app-flow-track { transition:none; }
    }
</style>

<div class="app-flow" data-app-flow data-initial-step="{{ $initialStep }}">
    <div class="app-flow-track" data-app-track>
        <section class="app-screen" aria-label="Bama app welcome">
            <div class="app-screen-center">
                <div class="app-logo">
                    <img src="{{ $brandLogoUrl }}" alt="Bama Solutions">
                </div>
                <div>
                    <div class="app-kicker">Bama Workspace</div>
                    <h1 class="app-title">Manage<br>Your<br>Business</h1>
                    <p class="app-copy">Sign up or log in to see business activity, finance, clients, stock, projects, and reports in one dashboard.</p>
                </div>
                <button class="app-primary" type="button" data-app-go="1">
                    Get Started <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </section>

        <section class="app-screen" aria-label="Choose app access method">
            <div class="app-screen-center">
                <div class="app-logo">
                    <img src="{{ $brandLogoUrl }}" alt="Bama Solutions">
                </div>
                <div class="app-choice-card">
                    <h2>Continue with email</h2>
                    <p>Use your Bama account to open the app dashboard, or create a workspace if your business is new.</p>
                    <div class="app-choice-actions">
                        <button class="app-primary mt-0" type="button" data-app-go="2">Continue With Email</button>
                        <a class="app-secondary" href="{{ route('register.account') }}">Create business account</a>
                    </div>
                    <div class="app-stat-grid" aria-label="System status">
                        <span><strong>{{ $system['workspaces'] }}</strong><small>Workspaces</small></span>
                        <span><strong>{{ $system['modules'] }}</strong><small>Modules</small></span>
                        <span><strong>{{ $system['industries'] }}</strong><small>Industries</small></span>
                        <span><strong>{{ $system['security'] }}</strong><small>Access</small></span>
                    </div>
                </div>
                @include('mobile.install-card')
            </div>
        </section>

        <section class="app-screen" aria-label="Bama app login">
            <div class="app-screen-center">
                <div class="app-auth-top">
                    <button class="app-icon-button" type="button" data-app-go="1" aria-label="Back">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="app-logo m-0">
                        <img src="{{ $brandLogoUrl }}" alt="Bama Solutions">
                    </div>
                    <span class="app-icon-button" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                </div>

                <div class="app-auth-card">
                    @if ($otpAvailable)
                        <ul class="nav nav-pills app-tabs" role="tablist">
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
                                <input id="app-login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                                <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="app-login-password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <button class="btn btn-warning w-100" type="submit">Sign in</button>
                        <a href="{{ route('register.account') }}" class="btn app-register-link w-100 mt-3">Create business account</a>
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

                    <div class="app-security">
                        <i class="bi bi-shield-check"></i>
                        <span>Accounts are checked against the system database and successful sign-ins open the dashboard.</span>
                    </div>
                </div>

                <div class="app-legal">
                    By signing up or logging in, I accept the Bama Terms of Service and Privacy Policy.
                </div>
            </div>
        </section>
    </div>

    <div class="app-dots" aria-label="App login progress">
        <button class="app-dot" type="button" data-app-go="0" aria-label="Welcome"></button>
        <button class="app-dot" type="button" data-app-go="1" aria-label="Continue"></button>
        <button class="app-dot" type="button" data-app-go="2" aria-label="Login"></button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const flow = document.querySelector('[data-app-flow]');
        const dots = document.querySelectorAll('.app-dot');
        let step = Number(flow?.dataset.initialStep || 0);
        let touchStartX = 0;
        let touchStartY = 0;

        const setStep = (next) => {
            if (! flow) return;
            step = Math.max(0, Math.min(2, Number(next)));
            flow.style.setProperty('--step', step);
            dots.forEach((dot, index) => dot.classList.toggle('active', index === step));

            if (step === 2) {
                setTimeout(() => document.querySelector('input[name="username"]')?.focus({ preventScroll:true }), 360);
            }
        };

        document.querySelectorAll('[data-app-go]').forEach((button) => {
            button.addEventListener('click', () => setStep(button.dataset.appGo));
        });

        flow?.addEventListener('touchstart', (event) => {
            touchStartX = event.touches[0].clientX;
            touchStartY = event.touches[0].clientY;
        }, { passive:true });

        flow?.addEventListener('touchend', (event) => {
            const dx = event.changedTouches[0].clientX - touchStartX;
            const dy = event.changedTouches[0].clientY - touchStartY;
            if (Math.abs(dx) < 54 || Math.abs(dx) < Math.abs(dy)) return;
            setStep(step + (dx < 0 ? 1 : -1));
        }, { passive:true });

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

        setStep(step);
    });
</script>
@endsection
