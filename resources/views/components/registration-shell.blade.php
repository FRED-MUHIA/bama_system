@props(['step' => 1])

@php
    $legacyLogoPath = 'logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png';
    $brand = (array) data_get(\App\Models\MarketingPage::resolve('home')->sections ?? [], 'brand', []);
    $configuredLogoPath = data_get($brand, 'logo_path');
    $registrationLogoUrl = $configuredLogoPath && $configuredLogoPath !== $legacyLogoPath
        ? \App\Support\PublicUpload::url($configuredLogoPath)
        : null;
    $registrationBrandName = str_replace(' Admin', '', config('app.name', 'Bama'));
    $registrationLogoAlt = data_get($brand, 'logo_alt', $registrationBrandName);
@endphp

<x-auth-layout variant="bare">
<main class="bg-[#F7F8F5] text-black">
    <style>
        @font-face {
            font-family: 'McQueen';
            src: local('McQueen SemiBold'), local('McQueen 600'), local('McQueen');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }

        .registration-page {
            --font-heading: 'Inter Tight', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-body: 'Inter', 'Inter Tight', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-family: var(--font-body) !important;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            font-optical-sizing: auto;
            font-synthesis-weight: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            width: 100%;
            min-width: 0;
            min-height: 100svh;
            min-height: 100dvh;
            overflow-x: clip;
        }

        .registration-page * { letter-spacing: 0; }

        .registration-page h1,
        .registration-page h2,
        .registration-page h3,
        .registration-page h4,
        .registration-page h5,
        .registration-page h6 {
            font-family: var(--font-heading) !important;
            font-weight: 600 !important;
            color: #000000;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            text-rendering: geometricPrecision;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .registration-page p,
        .registration-page a,
        .registration-page span,
        .registration-page label,
        .registration-page input,
        .registration-page select,
        .registration-page button,
        .registration-page textarea {
            font-family: var(--font-body) !important;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .registration-page .font-semibold,
        .registration-page .font-bold,
        .registration-page .font-black {
            font-weight: 500 !important;
        }

        .registration-page .field-control {
            width: 100%;
            min-height: 50px;
            border: 1px solid #d4d4d8;
            border-radius: 12px;
            background: #ffffff;
            color: #000000;
            font-size: 16px;
            box-shadow: 0 1px 0 rgba(15, 23, 42, .02);
        }

        .registration-page .field-control:focus {
            border-color: #00A651;
            box-shadow: 0 0 0 4px rgba(0, 166, 81, .12);
        }

        .registration-brand {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            color: #000000;
            text-decoration: none;
        }

        .registration-brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 10px;
            background: #00A651;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 900;
        }

        .registration-brand-name {
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1;
        }

        .registration-page button,
        .registration-page a {
            touch-action: manipulation;
        }

        .registration-page button:focus-visible,
        .registration-page a:focus-visible,
        .registration-page input:focus-visible,
        .registration-page select:focus-visible {
            outline: 3px solid rgba(0, 166, 81, .24);
            outline-offset: 2px;
        }

        .registration-page button:active,
        .registration-page a:active {
            transform: scale(.98);
        }

        .registration-content {
            min-width: 0;
            min-height: 100svh;
            min-height: 100dvh;
        }

        @media (max-width: 1023.98px) {
            .registration-content {
                min-height: auto;
                align-items: flex-start;
                padding-top: max(1rem, env(safe-area-inset-top));
                padding-bottom: max(1rem, env(safe-area-inset-bottom));
            }
        }
    </style>
    <div class="registration-page mx-auto grid min-h-screen max-w-7xl lg:grid-cols-[.72fr_1.28fr]">
        <aside class="relative hidden overflow-hidden border-r border-zinc-200 bg-white px-8 py-7 lg:block">
            <a href="{{ route('landing') }}" class="registration-brand" aria-label="Back to {{ $registrationBrandName }} home">
                @if($registrationLogoUrl)
                    <x-bama-logo variant="auth" :src="$registrationLogoUrl" :alt="$registrationLogoAlt" />
                @else
                    <x-bama-logo variant="auth" :alt="$registrationLogoAlt" />
                @endif
            </a>
            <div class="mt-14">
                <p class="text-xs font-semibold uppercase text-[#00A651]">Workspace setup</p>
                <h1 class="mt-4 max-w-sm text-4xl font-black leading-tight">Launch a tenant-ready business cloud.</h1>
                <p class="mt-4 max-w-md text-base leading-7 text-black">Your registration creates the tenant, business, owner account, subscription trial, modules, theme, and dashboard foundation.</p>
            </div>
            <div class="mt-8 space-y-3">
                @foreach ([1 => 'Create Account', 2 => 'Business Information', 3 => 'Choose Plan', 4 => 'Workspace Provisioning', 5 => 'Welcome Dashboard'] as $number => $label)
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg {{ $step >= $number ? 'bg-[#00A651] text-white' : 'bg-zinc-100 text-black' }} text-sm font-bold">{{ $number }}</span>
                        <span class="text-sm font-semibold text-black">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </aside>

        <section class="registration-content flex items-center justify-center px-4 py-5 sm:px-6">
            <div class="w-full min-w-0 max-w-[440px]">
                <div class="mb-4 flex min-w-0 items-center justify-between gap-3 lg:hidden">
                    <a href="{{ route('landing') }}" class="registration-brand" aria-label="Back to {{ $registrationBrandName }} home">
                        @if($registrationLogoUrl)
                            <x-bama-logo variant="compact" :src="$registrationLogoUrl" :alt="$registrationLogoAlt" />
                        @else
                            <x-bama-logo variant="compact" :alt="$registrationLogoAlt" />
                        @endif
                    </a>
                    <span class="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-sm text-black">Step {{ $step }} of 5</span>
                </div>
                {{ $slot }}
            </div>
        </section>
    </div>
</main>
</x-auth-layout>
