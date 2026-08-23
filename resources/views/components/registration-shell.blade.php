@props(['step' => 1])

<main class="min-h-screen bg-[#F7F8F5] text-black">
    <style>
        @font-face {
            font-family: 'McQueen';
            src: local('McQueen SemiBold'), local('McQueen 600'), local('McQueen');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }

        .registration-page {
            --font-heading: 'McQueen', 'tt_normsregular', 'TT Norms', 'Inter Tight', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            --font-body: 'tt_normsregular', 'TT Norms', 'Inter Tight', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
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
        .registration-page button {
            font-family: var(--font-body) !important;
        }

        .registration-page .font-semibold,
        .registration-page .font-bold,
        .registration-page .font-black {
            font-weight: 500 !important;
        }

        .registration-page .field-control {
            border: 1px solid #d4d4d8;
            background: #ffffff;
            color: #000000;
            box-shadow: 0 1px 0 rgba(15, 23, 42, .02);
        }

        .registration-page .field-control:focus {
            border-color: #00A651;
            box-shadow: 0 0 0 4px rgba(0, 166, 81, .12);
        }
    </style>
    <div class="registration-page mx-auto grid min-h-screen max-w-7xl lg:grid-cols-[.72fr_1.28fr]">
        <aside class="relative hidden overflow-hidden border-r border-zinc-200 bg-white px-8 py-7 lg:block">
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
                <img src="{{ asset('images/bama-logo.png') }}" alt="BAMA" class="h-9 w-auto">
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

        <section class="flex min-h-screen items-center justify-center px-4 py-5 sm:px-6">
            <div class="w-full max-w-4xl">
                <div class="mb-5 flex items-center justify-between lg:hidden">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
                        <img src="{{ asset('images/bama-logo.png') }}" alt="BAMA" class="h-9 w-auto">
                    </a>
                    <span class="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-sm text-black">Step {{ $step }} of 5</span>
                </div>
                {{ $slot }}
            </div>
        </section>
    </div>
</main>
