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
<main class="bg-[#050806] text-white">
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
            background-color: #050806;
            background-image:
                radial-gradient(circle at 50% 8%, rgba(223, 255, 69, .11), transparent 25rem),
                radial-gradient(circle, rgba(223, 255, 69, .1) 1px, transparent 1.25px);
            background-size: auto, 13px 13px;
            color: #f7f9f2;
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
            color: #f7f9f2;
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
            border: 1px solid #2c332a;
            border-radius: 12px;
            background: rgba(2, 5, 3, .82);
            color: #f7f9f2;
            font-size: 16px;
            box-shadow: 0 1px 0 rgba(15, 23, 42, .02);
        }

        .registration-page .field-control:focus {
            border-color: #dfff45;
            background: #090d08;
            box-shadow: 0 0 0 3px rgba(223, 255, 69, .13);
        }

        .registration-brand {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            color: #f7f9f2;
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

        .registration-page .text-black { color: #f7f9f2 !important; }
        .registration-page .text-zinc-500,
        .registration-page .text-zinc-600 { color: #9da59a !important; }
        .registration-page .text-\[\#00A651\],
        .registration-page .text-\[\#007A3B\] { color: #dfff45 !important; }
        .registration-page .bg-white { background-color: #090d08 !important; }
        .registration-page [class*="bg-[#EAF8F0]"] { background-color: #10180b !important; }
        .registration-page .border-zinc-200,
        .registration-page .border-zinc-300 { border-color: #2c332a !important; }
        .registration-page [class*="bg-[#00A651]"] {
            background: linear-gradient(90deg, #c8f32f, #e8ff59) !important;
            color: #071006 !important;
            box-shadow: 0 14px 34px rgba(202, 246, 50, .12) !important;
        }

        .registration-card {
            border-color: rgba(223, 255, 69, .09) !important;
            background: linear-gradient(180deg, rgba(13, 18, 11, .9), rgba(5, 8, 6, .97)) !important;
            box-shadow: 0 28px 80px rgba(0, 0, 0, .34) !important;
        }

        .registration-orb {
            position: relative;
            display: grid;
            width: 76px;
            height: 76px;
            place-items: center;
            margin: 6px auto 28px;
            border: 1px solid rgba(223, 255, 69, .5);
            border-radius: 50%;
            background: radial-gradient(circle at 38% 30%, rgba(241, 255, 163, .35), transparent 22%), radial-gradient(circle, #425d0b, #121d05 58%, #050806 76%);
            box-shadow: 0 0 0 7px rgba(223, 255, 69, .035), 0 0 34px rgba(199, 238, 54, .2), inset 0 0 20px rgba(223, 255, 69, .22);
        }

        .registration-orb::before {
            content: '';
            position: absolute;
            inset: -15px;
            border: 1px solid rgba(223, 255, 69, .08);
            border-radius: 50%;
        }

        .registration-orb .bama-brand-logo {
            width: 46px;
            filter: brightness(1.12) drop-shadow(0 0 10px rgba(223, 255, 69, .35));
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

            .registration-card {
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
            }
        }

        @media (min-width: 1024px) {
            .registration-orb { display: none; }
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
                <div class="registration-orb" aria-hidden="true">
                    <x-bama-logo variant="splash" mark alt="" />
                </div>
                {{ $slot }}
            </div>
        </section>
    </div>
</main>
</x-auth-layout>
