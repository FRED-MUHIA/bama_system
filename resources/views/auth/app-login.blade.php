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
    $registerUrl = \Illuminate\Support\Facades\Route::has('register.account') ? route('register.account') : null;
@endphp

<style>
    html,
    body,
    html:has(.app-flow),
    body:has(.app-flow) {
        width:100%;
        max-width:100%;
        overflow:hidden;
    }
    body,
    body:has(.app-flow) { padding-bottom:0 !important; background:#070a12 !important; overscroll-behavior:none; }
    .container-fluid,
    body:has(.app-flow) .container-fluid {
        --bs-gutter-x:0;
        width:100%;
        max-width:100%;
        padding-left:0 !important;
        padding-right:0 !important;
        overflow:hidden;
    }
    .container-fluid > .row,
    body:has(.app-flow) .container-fluid > .row {
        --bs-gutter-x:0;
        width:100%;
        max-width:100%;
        min-height:100vh;
        margin-left:0 !important;
        margin-right:0 !important;
    }
    main[class*="col-"],
    body:has(.app-flow) main[class*="col-"] {
        flex:0 0 100% !important;
        width:100% !important;
        max-width:100% !important;
        padding-left:0 !important;
        padding-right:0 !important;
    }
    main > section,
    main > section:has(.app-flow) { max-width:none; padding:0 !important; overflow:hidden; }
    main > section > .alert,
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
    body .guest-theme-toggle,
    body:has(.app-flow) .guest-theme-toggle { display:none !important; }
    .bama-loading-screen {
        display:none !important;
        pointer-events:none !important;
    }

    .app-flow {
        --step:0;
        --brand:#00A651;
        --brand-dark:#007A3B;
        --brand-lime:#dfff45;
        --brand-soft:#EAF8F0;
        --night:#050806;
        --surface:#071B12;
        --surface-raised:#0b140d;
        --field:#040705;
        --line:rgba(223,255,69,.14);
        position:relative;
        width:100%;
        max-width:100%;
        min-height:100vh;
        min-height:100svh;
        height:100vh;
        height:var(--bama-visual-viewport-height,100dvh);
        overflow:hidden;
        color:#fff;
        background:var(--night);
        touch-action:pan-y pinch-zoom;
    }
    .app-flow * { letter-spacing:0; }
    .app-flow-track {
        display:flex;
        width:100%;
        min-width:100%;
        height:100%;
        transform:translate3d(calc((var(--step) * -100%) + var(--drag-offset, 0px)),0,0);
        transition:transform .26s cubic-bezier(.2,.78,.18,1);
        will-change:transform;
    }
    .app-flow.is-dragging .app-flow-track {
        transition:none;
    }
    .app-screen {
        position:relative;
        flex:0 0 100%;
        width:100%;
        min-width:0;
        min-height:100%;
        padding:calc(24px + env(safe-area-inset-top)) clamp(18px,5vw,42px) calc(28px + env(safe-area-inset-bottom));
        overflow-x:hidden;
        overflow-y:auto;
        scroll-padding-block:96px;
        background-color:var(--night);
        background-image:
            radial-gradient(circle at 50% 13%,rgba(223,255,69,.1),transparent 24rem),
            linear-gradient(180deg,#071B12,#050806 66%);
        background-size:auto;
        -webkit-overflow-scrolling:touch;
    }
    .app-screen::after {
        content:"";
        position:absolute;
        inset:0;
        background:
            radial-gradient(circle at 50% 9%,rgba(0,166,81,.12),transparent 24%),
            linear-gradient(180deg,rgba(223,255,69,.03),transparent 30%);
        pointer-events:none;
    }
    .app-screen > * { position:relative; z-index:1; }
    .app-screen-center {
        width:min(100%,440px);
        max-width:100%;
        min-height:calc(100dvh - 52px - env(safe-area-inset-top) - env(safe-area-inset-bottom));
        margin-inline:auto;
        display:flex;
        flex-direction:column;
        padding-bottom:34px;
    }
    .app-logo {
        display:flex;
        justify-content:center;
        margin-bottom:clamp(24px,7dvh,56px);
    }
    .app-logo .bama-brand-logo {
        width:clamp(92px,30vw,118px);
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
        background:var(--brand);
        box-shadow:0 0 18px rgba(0,166,81,.34);
    }
    .app-title {
        margin:20px 0 0;
        max-width:690px;
        color:#fff;
        font-size:clamp(2.15rem,10vw,3.25rem);
        font-weight:850 !important;
        line-height:.98;
        text-shadow:0 18px 45px rgba(0,0,0,.32);
    }
    .app-copy {
        max-width:540px;
        margin:16px 0 0;
        color:rgba(255,255,255,.76);
        font-size:clamp(.98rem,3.7vw,1.18rem);
        line-height:1.42;
    }
    .app-primary,
    .app-secondary {
        width:100%;
        min-height:50px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:12px;
        border-radius:14px;
        border:0;
        text-decoration:none;
        text-align:center;
        font-size:.975rem;
        font-weight:700;
        padding-inline:18px;
        touch-action:manipulation;
        -webkit-tap-highlight-color:transparent;
        transition:transform .15s ease,filter .15s ease,background-color .15s ease;
    }
    .app-primary {
        background:linear-gradient(90deg,var(--brand),var(--brand-dark));
        color:#fff;
        box-shadow:0 18px 44px rgba(0,166,81,.2);
    }
    .app-primary:hover { color:#fff; filter:brightness(1.05); }
    .app-secondary {
        border:1px solid rgba(255,255,255,.13);
        background:rgba(255,255,255,.035);
        color:#f6f8f2 !important;
    }
    .app-secondary:hover { color:#fff !important; background:rgba(255,255,255,.08); }
    .app-welcome-actions {
        margin-top:auto;
        display:grid;
        gap:12px;
        padding-top:28px;
    }
    .app-direct-actions {
        display:grid;
        grid-template-columns:minmax(0,1fr);
        gap:10px;
    }
    .app-link-button {
        min-height:44px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        border:1px solid rgba(255,255,255,.14);
        border-radius:12px;
        background:rgba(255,255,255,.055);
        color:#f7f9f2;
        text-decoration:none;
        font-size:.9rem;
        font-weight:850;
        text-align:center;
        touch-action:manipulation;
        -webkit-tap-highlight-color:transparent;
    }
    .app-link-button:hover,
    .app-link-button:focus {
        color:#fff;
        background:rgba(255,255,255,.1);
    }
    .app-link-button--register {
        border-color:rgba(0,166,81,.42);
        background:rgba(0,166,81,.12);
        color:#f7f9f2;
    }
    .app-link-button--register i {
        color:var(--brand-lime);
    }

    .app-choice-card,
    .app-auth-card {
        width:min(100%,420px);
        margin-inline:auto;
        border:1px solid var(--line);
        background:linear-gradient(180deg,rgba(11,20,13,.9),rgba(5,8,6,.96));
        box-shadow:0 30px 70px rgba(0,0,0,.34);
        backdrop-filter:blur(12px);
    }
    .app-choice-card {
        margin-top:auto;
        padding:20px;
        border-radius:18px;
    }
    .app-choice-card h2,
    .app-auth-card h2 {
        margin:0 0 10px;
        color:#fff;
        font-size:clamp(1.65rem,6vw,2.25rem);
        font-weight:850 !important;
        line-height:1.08;
    }
    .app-choice-card p {
        margin:0 0 20px;
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
        width:min(100%,420px);
        margin:0 auto 14px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
    }
    .app-auth-top--solo {
        justify-content:center;
        margin-bottom:18px;
    }
    .app-auth-top--solo .bama-brand-logo {
        width:clamp(92px,30vw,118px);
        filter:drop-shadow(0 14px 28px rgba(0,0,0,.2));
    }
    .app-icon-button {
        width:44px;
        height:44px;
        display:grid;
        place-items:center;
        border:0;
        border-radius:50%;
        background:rgba(255,255,255,.08);
        color:#fff;
    }
    .app-icon-button:hover,
    .app-icon-button:focus {
        background:rgba(0,166,81,.2);
        color:#fff;
    }
    .app-auth-card {
        padding:18px;
        border-radius:18px;
    }
    .app-auth-heading {
        margin-bottom:16px;
        text-align:center;
    }
    .app-auth-heading h2 {
        margin:0;
        font-size:clamp(1.65rem,7vw,2rem);
    }
    .app-auth-heading p {
        margin:6px 0 0;
        color:rgba(255,255,255,.72);
        font-size:.9rem;
    }
    .app-tabs {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:4px;
        margin:0 0 18px !important;
        padding:4px;
        border:1px solid var(--line);
        border-radius:14px;
        background:rgba(255,255,255,.045);
    }
    .app-tabs .nav-item { display:grid; }
    .app-tabs .nav-link {
        min-height:40px;
        border-radius:10px;
        color:rgba(255,255,255,.72) !important;
        font-size:.74rem;
        font-weight:950;
        padding:.42rem .28rem;
        white-space:normal;
        line-height:1.08;
    }
    .app-tabs .nav-link.active {
        background:var(--brand);
        color:#fff !important;
    }
    .app-auth-card .form-label {
        margin-bottom:6px;
        color:var(--brand-lime) !important;
        font-size:.86rem;
        font-weight:950;
        text-transform:uppercase;
    }
    .app-auth-card .form-control {
        min-height:50px;
        border:1px solid rgba(223,255,69,.16);
        border-radius:12px;
        background:rgba(4,7,5,.82);
        color:#fff !important;
        -webkit-text-fill-color:#fff;
        font-size:16px;
        padding:.7rem .95rem;
        box-shadow:inset 0 0 12px rgba(0,0,0,.2);
    }
    .app-auth-card .form-control:focus {
        border-color:var(--brand-lime);
        background:#090d08;
        color:#fff !important;
        -webkit-text-fill-color:#fff;
        box-shadow:0 0 0 .2rem rgba(223,255,69,.13);
    }
    .app-auth-card .form-control::placeholder {
        color:rgba(255,255,255,.48) !important;
        -webkit-text-fill-color:rgba(255,255,255,.48);
    }
    .app-auth-card .form-control:-webkit-autofill,
    .app-auth-card .form-control:-webkit-autofill:hover,
    .app-auth-card .form-control:-webkit-autofill:focus {
        -webkit-text-fill-color:#fff !important;
        box-shadow:0 0 0 1000px #040705 inset, 0 0 0 .2rem rgba(223,255,69,.13);
        caret-color:#fff;
    }
    .app-auth-card .form-check-input {
        border-color:rgba(255,255,255,.4);
        background-color:rgba(255,255,255,.08);
    }
    .app-auth-card .form-check-input:checked {
        background-color:var(--brand);
        border-color:var(--brand);
    }
    .app-auth-card .form-check-input:focus {
        border-color:var(--brand-lime);
        box-shadow:0 0 0 .2rem rgba(223,255,69,.13);
    }
    .app-auth-card .form-check-label,
    .app-auth-card .text-muted {
        color:rgba(255,255,255,.72) !important;
    }
    .app-auth-card a {
        color:var(--brand-lime);
        font-weight:950;
        text-decoration-thickness:2px;
        text-underline-offset:4px;
    }
    .app-auth-card .btn-link {
        color:var(--brand-lime) !important;
        font-weight:950;
        text-decoration-thickness:2px;
        text-underline-offset:4px;
    }
    .app-auth-card .btn-link:disabled,
    .app-auth-card [class*="text-slate-"],
    .app-auth-card [class*="text-gray-"],
    .app-auth-card [class*="text-zinc-"],
    .app-auth-card [class*="text-blue-"],
    .app-auth-card [class*="text-indigo-"] {
        color:rgba(255,255,255,.76) !important;
    }
    .app-auth-card .btn-warning {
        min-height:50px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:10px;
        border:0;
        border-radius:14px;
        background:linear-gradient(90deg,var(--brand),var(--brand-dark));
        color:#fff;
        font-size:.975rem;
        font-weight:700;
        box-shadow:0 18px 44px rgba(0,166,81,.2);
    }
    .app-auth-card .btn-warning:hover,
    .app-auth-card .btn-warning:focus {
        background:var(--brand-dark);
        color:#fff;
    }
    .app-auth-card .btn-warning.is-loading {
        opacity:.82;
        cursor:wait;
    }
    .app-auth-card .btn-link[type="submit"] {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
    }
    .app-button-spinner {
        width:1rem;
        height:1rem;
        border:2px solid rgba(255,255,255,.42);
        border-top-color:currentColor;
        border-radius:50%;
        animation:app-button-spin .7s linear infinite;
    }
    .app-auth-links {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin:0 0 16px;
        font-size:.82rem;
    }
    .app-auth-links .form-check {
        min-height:44px;
        display:flex;
        align-items:center;
        margin:0;
    }
    .app-auth-links > a {
        min-height:44px;
        display:inline-flex;
        align-items:center;
        text-align:right;
    }
    .app-auth-register {
        min-height:44px;
        margin-top:14px;
        display:flex;
        align-items:center;
        justify-content:center;
        flex-wrap:wrap;
        gap:8px;
        color:rgba(255,255,255,.72);
        font-size:.82rem;
        line-height:1.35;
        text-align:center;
    }
    .app-auth-register a {
        min-height:34px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
    }
    .password-wrap { position:relative; }
    .password-wrap .form-control { padding-right:58px; }
    .password-toggle {
        position:absolute;
        top:50%;
        right:8px;
        width:44px;
        height:44px;
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
    .app-security i { color:var(--brand); margin-top:1px; }
    .app-legal {
        width:min(100%,520px);
        margin:22px auto 0;
        color:rgba(255,255,255,.62);
        font-size:.76rem;
        line-height:1.5;
        text-align:center;
    }
    .app-flow .bama-install-card {
        border-color:var(--line);
        background:linear-gradient(180deg,rgba(11,20,13,.9),rgba(5,8,6,.96));
        color:#f7f9f2;
        box-shadow:0 24px 54px rgba(0,0,0,.26);
    }
    .app-flow .bama-install-card span,
    .app-flow .bama-install-card p {
        color:rgba(255,255,255,.76);
    }
    .app-flow .bama-install-actions button:last-child {
        border-color:rgba(255,255,255,.14);
        background:rgba(255,255,255,.08);
        color:#f7f9f2;
    }
    .otp-success {
        border-color:rgba(0,166,81,.32);
        background:rgba(0,166,81,.12);
        color:#eaf8f0;
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
        background:var(--brand);
    }
    @keyframes app-button-spin {
        to { transform:rotate(360deg); }
    }
    @media (min-width:768px) {
        .app-screen {
            display:grid;
            place-items:center;
            padding-inline:32px;
        }
        .app-screen-center {
            width:min(100%,560px);
            min-height:min(760px, calc(100dvh - 64px));
        }
        .app-choice-card,
        .app-auth-card,
        .app-auth-top {
            width:min(100%,440px);
        }
        .app-title {
            font-size:clamp(3rem,7vw,4rem);
            line-height:.95;
        }
        .app-primary,
        .app-secondary,
        .app-link-button,
        .app-auth-card .btn-warning {
            min-height:52px;
            border-radius:16px;
        }
        .app-tabs {
            border-radius:999px;
        }
        .app-tabs .nav-link {
            border-radius:999px;
            white-space:nowrap;
        }
        .app-auth-card .form-control {
            min-height:52px;
            border-radius:14px;
            padding:.8rem 1rem;
        }
    }
    @media (max-width:767.98px) {
        .app-screen {
            padding-inline:clamp(20px,6vw,26px);
        }
        .app-screen-center {
            width:min(100%,420px);
            text-align:center;
        }
        .app-kicker {
            justify-content:center;
            gap:12px;
        }
        .app-kicker::before {
            width:42px;
        }
        .app-title,
        .app-copy {
            margin-left:auto;
            margin-right:auto;
            text-align:center;
        }
        .app-auth-card,
        .app-auth-card form,
        .app-auth-links {
            text-align:left;
        }
        .app-auth-heading,
        .app-auth-card .otp-success {
            text-align:center;
        }
        .app-screen:nth-child(3) {
            padding-top:calc(14px + env(safe-area-inset-top));
        }
        .app-screen:nth-child(3) .app-screen-center {
            justify-content:flex-start;
            min-height:auto;
            padding-bottom:44px;
        }
        .app-auth-top .app-logo .bama-brand-logo {
            width:96px;
        }
        .app-auth-card {
            padding-inline:0;
            border:0;
            background:transparent;
            box-shadow:none;
            backdrop-filter:none;
        }
        .app-icon-button {
            width:42px;
            height:42px;
        }
        .app-security {
            font-size:.74rem;
        }
        .app-legal {
            margin-top:14px;
            font-size:.7rem;
        }
    }
    @media (max-width:380px) {
        .app-screen { padding-inline:14px; }
        .app-title { font-size:clamp(2.15rem,10vw,2.625rem); }
        .app-auth-card,
        .app-choice-card { padding-inline:14px; }
        .app-tabs .nav-link { font-size:.68rem; }
        .app-auth-card .form-label { font-size:.78rem; }
        .app-direct-actions { gap:8px; }
        .app-link-button { font-size:.84rem; padding-inline:10px; }
    }
    @media (max-height:680px) and (max-width:767.98px) {
        .app-logo {
            margin-bottom:16px;
        }
        .app-title {
            margin-top:16px;
            font-size:clamp(2.1rem,9vw,2.5rem);
        }
        .app-copy {
            margin-top:14px;
        }
        .app-choice-card {
            margin-top:24px;
        }
        .app-welcome-actions {
            padding-top:18px;
        }
        .app-stat-grid {
            display:none;
        }
    }
    @media (prefers-reduced-motion:reduce) {
        .app-flow-track { transition:none; }
    }
</style>

<x-auth-layout variant="bare">
<div class="app-flow" data-app-flow data-initial-step="{{ $initialStep }}">
    <div class="app-flow-track" data-app-track>
        <section class="app-screen" id="app-step-welcome" data-app-screen aria-label="Bama app welcome">
            <div class="app-screen-center">
                <div class="app-logo">
                    <x-bama-logo variant="auth" :src="$brandLogoUrl" alt="BAMA" />
                </div>
                <div>
                    <div class="app-kicker">Bama Web App</div>
                    <h1 class="app-title">Workspace<br>Console</h1>
                    <p class="app-copy">Use your existing workspace credentials to open dashboards, operations, finance, clients, stock, projects, and reports.</p>
                </div>
                <div class="app-welcome-actions">
                    <button class="app-primary" type="button" data-app-go="1">
                        Get Started <i class="bi bi-arrow-right"></i>
                    </button>
                    <div class="app-direct-actions" aria-label="Account access">
                        <button class="app-link-button" type="button" data-app-go="2">
                            <i class="bi bi-box-arrow-in-right"></i> Workspace Login
                        </button>
                        @if ($registerUrl)
                            <a class="app-link-button app-link-button--register" href="{{ $registerUrl }}">
                                <i class="bi bi-person-plus"></i> Create Account
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="app-screen" id="app-step-access" data-app-screen aria-label="Choose app access method">
            <div class="app-screen-center">
                <div class="app-auth-top">
                    <button class="app-icon-button" type="button" data-app-go="0" aria-label="Back to welcome">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <span class="app-icon-button" aria-hidden="true"><i class="bi bi-envelope-check"></i></span>
                </div>
                <div class="app-logo">
                    <x-bama-logo variant="auth" :src="$brandLogoUrl" alt="BAMA" />
                </div>
                <div class="app-choice-card">
                    <h2>App access</h2>
                    <p>This console is for existing workspace users. New user access is issued by the workspace administrator.</p>
                    <div class="app-choice-actions">
                        <button class="app-primary mt-0" type="button" data-app-go="2">Continue to Sign In</button>
                        @if ($registerUrl)
                            <a class="app-secondary" href="{{ $registerUrl }}">
                                <i class="bi bi-person-plus"></i> Create Account
                            </a>
                        @endif
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

        <section class="app-screen" id="app-step-login" data-app-screen aria-label="Bama app login">
            <div class="app-screen-center">
                <div class="app-auth-top">
                    <button class="app-icon-button" type="button" data-app-go="1" aria-label="Back to access options">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <span class="app-icon-button" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                </div>

                <div class="app-auth-card">
                    <div class="app-auth-heading">
                        <h2>Workspace Sign In</h2>
                        <p>Authorized app users only</p>
                    </div>
                    @if ($otpAvailable)
                        <ul class="nav nav-pills app-tabs" role="tablist">
                            <li class="nav-item">
                                <button id="password-login-tab" class="nav-link {{ $otpSent ? '' : 'active' }}" data-bs-toggle="pill" data-bs-target="#password-login" type="button" role="tab" aria-controls="password-login" aria-selected="{{ $otpSent ? 'false' : 'true' }}">Password</button>
                            </li>
                            <li class="nav-item">
                                <button id="otp-login-tab" class="nav-link {{ $otpSent ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#otp-login" type="button" role="tab" aria-controls="otp-login" aria-selected="{{ $otpSent ? 'true' : 'false' }}">OTP</button>
                            </li>
                            <li class="nav-item">
                                <button id="magic-login-tab" class="nav-link" data-bs-toggle="pill" data-bs-target="#magic-login" type="button" role="tab" aria-controls="magic-login" aria-selected="false">Magic link</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade {{ $otpSent ? '' : 'show active' }}" id="password-login" role="tabpanel" aria-labelledby="password-login-tab">
                    @endif

                    <form method="post" action="{{ $loginActions['password'] }}">
                        @csrf
                        <input type="hidden" name="login_context" value="{{ $loginContext }}">
                        <div class="mb-3">
                            <label class="form-label" for="app-login-username">Email or username</label>
                            <input id="app-login-username" name="username" type="text" inputmode="email" value="{{ old('username') }}" class="form-control" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="app-login-password">Password</label>
                            <div class="password-wrap">
                                <input id="app-login-password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                                <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="app-login-password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="app-auth-links">
                            <label class="form-check gap-2">
                                <input class="form-check-input" type="checkbox" name="remember">
                                <span class="form-check-label">Remember me</span>
                            </label>
                            <a href="{{ route('password.request') }}">Forgot password?</a>
                        </div>
                        <button class="btn btn-warning w-100" type="submit"><i class="bi bi-stars me-2"></i> Sign in</button>
                    </form>

                    @if ($otpAvailable)
                            </div>
                            <div class="tab-pane fade {{ $otpSent ? 'show active' : '' }}" id="otp-login" role="tabpanel" aria-labelledby="otp-login-tab">
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
                                            <label class="form-label" for="app-otp-code">Verification code</label>
                                            <input id="app-otp-code" class="form-control text-center fs-4" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                                        </div>
                                        <button class="btn btn-warning w-100" type="submit">Verify OTP</button>
                                    </form>
                                    <div class="text-center mt-3">
                                        <small class="text-muted d-block mb-2">Check your inbox and spam folder.</small>
                                        <form method="post" action="{{ $loginActions['otpRequest'] }}">
                                            @csrf
                                            <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                            <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                            <button id="resend-otp" class="btn btn-link" type="submit" disabled data-ready-at="{{ session('otp_resend_at') }}">Resend OTP in <span id="otp-countdown">60</span>s</button>
                                        </form>
                                    </div>
                                @else
                                    <form method="post" action="{{ $loginActions['otpRequest'] }}">
                                        @csrf
                                        <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                        <div class="mb-3">
                                            <label class="form-label" for="app-otp-email">Work email</label>
                                            <input id="app-otp-email" class="form-control" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autocapitalize="none" spellcheck="false" required>
                                        </div>
                                        <button class="btn btn-warning w-100" type="submit">Send one-time code</button>
                                    </form>
                                @endif
                            </div>
                            <div class="tab-pane fade" id="magic-login" role="tabpanel" aria-labelledby="magic-login-tab">
                                <form method="post" action="{{ $loginActions['magic'] }}">
                                    @csrf
                                    <input type="hidden" name="login_context" value="{{ $loginContext }}">
                                    <div class="mb-3">
                                        <label class="form-label" for="app-magic-email">Work email</label>
                                        <input id="app-magic-email" class="form-control" name="email" type="email" autocomplete="email" autocapitalize="none" spellcheck="false" required>
                                    </div>
                                    <button class="btn btn-warning w-100" type="submit">Email secure login link</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="app-security">
                        <i class="bi bi-shield-check"></i>
                        <span>Accounts are checked against the workspace database before the dashboard opens.</span>
                    </div>
                    @if ($registerUrl)
                        <div class="app-auth-register">
                            <span>Do not have an account?</span>
                            <a href="{{ $registerUrl }}"><i class="bi bi-person-plus"></i> Create Account</a>
                        </div>
                    @endif
                </div>

                <div class="app-legal">
                    By signing in, I accept the Bama Terms of Service and Privacy Policy.
                </div>
            </div>
        </section>
    </div>

    <div class="app-dots" aria-label="App login progress">
        <button class="app-dot" type="button" data-app-go="0" aria-label="Welcome" aria-controls="app-step-welcome"></button>
        <button class="app-dot" type="button" data-app-go="1" aria-label="Continue" aria-controls="app-step-access"></button>
        <button class="app-dot" type="button" data-app-go="2" aria-label="Login" aria-controls="app-step-login"></button>
    </div>
</div>

<script>
    (() => {
        const flow = document.querySelector('[data-app-flow]');
        if (! flow) return;

        const screens = Array.from(flow?.querySelectorAll('[data-app-screen]') || []);
        const dots = document.querySelectorAll('.app-dot');
        const interactiveTouchSelector = 'a, button, input, textarea, select, label, summary, [role="button"], [contenteditable="true"], .app-auth-card, .bama-install-card';
        let step = Number(flow?.dataset.initialStep || 0);
        let touchStartX = 0;
        let touchStartY = 0;
        let touchDeltaX = 0;
        let touchDeltaY = 0;
        let swipeAllowed = false;
        let isSwiping = false;

        const syncHistory = (next) => {
            if (! window.history?.pushState) return;
            window.history.pushState({ appFlow: true, step: next }, '', window.location.href);
        };

        const focusLoginField = () => {
            const loginScreen = screens[2];
            if (! loginScreen) return;

            const activePane = loginScreen.querySelector('.tab-pane.show.active') || loginScreen;
            const activeInput = activePane.querySelector('[autofocus], input:not([type="hidden"]), select, textarea');
            window.setTimeout(() => activeInput?.focus({ preventScroll:true }), 360);
        };

        const setStep = (next, options = {}) => {
            if (! flow) return;
            const previousStep = step;
            step = Math.max(0, Math.min(2, Number(next)));
            flow.style.setProperty('--step', step);
            flow.style.setProperty('--drag-offset', '0px');
            flow.classList.remove('is-dragging');

            screens.forEach((screen, index) => {
                const active = index === step;
                screen.toggleAttribute('aria-hidden', ! active);
                screen.inert = ! active;
            });

            dots.forEach((dot, index) => {
                const active = index === step;
                dot.classList.toggle('active', active);
                dot.setAttribute('aria-current', active ? 'step' : 'false');
                dot.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            if (options.history && step !== previousStep) {
                syncHistory(step);
            }

            if (step === 2 && (step !== previousStep || options.focus)) {
                focusLoginField();
            }
        };

        flow.querySelectorAll('[data-app-go]').forEach((button) => {
            button.addEventListener('click', () => setStep(button.dataset.appGo, { history:true }));
        });

        const authTabs = Array.from(flow?.querySelectorAll('.app-tabs [data-bs-target]') || []);
        const setAuthTab = (tab, shouldFocus = true) => {
            if (! flow || ! tab) return;

            authTabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            flow.querySelectorAll('.app-auth-card .tab-pane').forEach((panel) => {
                const active = `#${panel.id}` === tab.dataset.bsTarget;
                panel.classList.toggle('active', active);
                panel.classList.toggle('show', active);
                panel.hidden = ! active;
                panel.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            if (shouldFocus) {
                focusLoginField();
            }
        };

        authTabs.forEach((tab) => {
            tab.addEventListener('click', (event) => {
                event.preventDefault();
                setAuthTab(tab);
            });
        });

        if (authTabs.length) {
            setAuthTab(authTabs.find((tab) => tab.classList.contains('active')) || authTabs[0], false);
        }

        flow?.querySelectorAll('.app-tabs [data-bs-target]').forEach((tab) => {
            tab.addEventListener('shown.bs.tab', () => setAuthTab(tab));
        });

        flow?.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const submittedBy = event.submitter instanceof HTMLButtonElement ? event.submitter : null;
                const button = submittedBy || form.querySelector('button[type="submit"]');

                if (! button || button.disabled) return;

                button.disabled = true;
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
                button.dataset.originalLabel = button.textContent.trim();
                button.replaceChildren(
                    Object.assign(document.createElement('span'), { className: 'app-button-spinner' }),
                    document.createTextNode('Please wait')
                );
            });
        });

        const resetDrag = () => {
            flow.style.setProperty('--drag-offset', '0px');
            flow.classList.remove('is-dragging');
            isSwiping = false;
        };

        flow.addEventListener('touchstart', (event) => {
            touchStartX = event.touches[0].clientX;
            touchStartY = event.touches[0].clientY;
            touchDeltaX = 0;
            touchDeltaY = 0;
            const touchTarget = event.target instanceof Element ? event.target : null;
            swipeAllowed = event.touches.length === 1 && ! touchTarget?.closest(interactiveTouchSelector);
            isSwiping = false;
        }, { passive:true });

        flow.addEventListener('touchmove', (event) => {
            if (! swipeAllowed || event.touches.length !== 1) return;

            touchDeltaX = event.touches[0].clientX - touchStartX;
            touchDeltaY = event.touches[0].clientY - touchStartY;

            if (! isSwiping) {
                if (Math.abs(touchDeltaX) < 28) return;

                if (Math.abs(touchDeltaX) < Math.abs(touchDeltaY) * 1.35) {
                    swipeAllowed = false;
                    resetDrag();
                    return;
                }

                isSwiping = true;
            }

            event.preventDefault();

            const edgeResistance = (step === 0 && touchDeltaX > 0) || (step === 2 && touchDeltaX < 0) ? .24 : .72;
            flow.classList.add('is-dragging');
            flow.style.setProperty('--drag-offset', `${Math.round(touchDeltaX * edgeResistance)}px`);
        }, { passive:false });

        flow.addEventListener('touchend', (event) => {
            if (! isSwiping) {
                resetDrag();
                return;
            }

            const dx = event.changedTouches[0].clientX - touchStartX;
            const dy = event.changedTouches[0].clientY - touchStartY;
            if (Math.abs(dx) >= 64 && Math.abs(dx) > Math.abs(dy) * 1.35) {
                setStep(step + (dx < 0 ? 1 : -1), { history:true });
                return;
            }

            setStep(step);
        }, { passive:true });

        flow.addEventListener('touchcancel', () => resetDrag(), { passive:true });

        document.addEventListener('keydown', (event) => {
            if (! flow || ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target?.tagName)) return;
            if (event.key === 'ArrowRight') setStep(step + 1, { history:true });
            if (event.key === 'ArrowLeft') setStep(step - 1, { history:true });
        });

        window.addEventListener('popstate', (event) => {
            if (event.state?.appFlow) {
                setStep(event.state.step);
            }
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

        setStep(step, { focus: step === 2 });

        if (window.history?.replaceState) {
            window.history.replaceState({ appFlow: true, step }, '', window.location.href);
        }
    })();
</script>
</x-auth-layout>
@endsection
