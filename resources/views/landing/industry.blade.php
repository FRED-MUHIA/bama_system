@extends('layouts.marketing', ['title' => $industry['industry'].' | Bama Industry Solution'])

@php
    $slug = str_replace('_', '-', $industry['slug']);
    $modules = collect($industry['modules'] ?? []);
    $features = collect($industry['dashboard']['dashboard_features'] ?? $industry['dashboard']['features'] ?? []);
    $subIndustries = collect($industry['sub_industries'] ?? []);
    $workflows = collect($industry['workflows'] ?? []);
    $reports = collect($industry['reports'] ?? []);
    $roles = collect($industry['roles'] ?? []);
    $menus = collect($industry['dashboard']['menu_structure'] ?? $industry['menus'] ?? [])->map(fn ($menu) => is_array($menu) ? ($menu['label'] ?? $menu['module'] ?? 'Module') : $menu);
    $brandLogoUrl = \App\Support\PublicUpload::url('logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png') ?: asset('images/bama-solutions-02.png');
    $accent = ['#00A651', '#071B12'];
    $featureIcon = function ($feature) {
        $label = str($feature)->lower();

        return match (true) {
            $label->contains(['progress', 'milestone', 'status']) => 'bi-kanban',
            $label->contains(['budget', 'actual', 'cost control']) => 'bi-calculator',
            $label->contains(['revenue', 'profit', 'sales']) => 'bi-graph-up-arrow',
            $label->contains(['cash', 'payment', 'receivable']) => 'bi-cash-coin',
            $label->contains(['material', 'stock', 'inventory', 'consumption']) => 'bi-box-seam',
            $label->contains(['tender', 'conversion', 'pipeline']) => 'bi-funnel',
            $label->contains(['client', 'customer', 'guest', 'member']) => 'bi-people',
            $label->contains(['booking', 'reservation', 'appointment']) => 'bi-calendar-check',
            $label->contains(['compliance', 'quality', 'safety']) => 'bi-shield-check',
            $label->contains(['report', 'analytics', 'dashboard']) => 'bi-bar-chart',
            default => 'bi-speedometer2',
        };
    };
@endphp

@section('body')
<main class="bama-page min-h-screen" style="--accent: {{ $accent[0] }}; --dark: {{ $accent[1] }};">
    <header class="bama-header sticky top-0 z-50">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 text-black no-underline">
                <img src="{{ $brandLogoUrl }}" alt="Bama Solutions" class="bama-logo">
            </a>
            <div class="hidden items-center gap-6 text-sm font-bold text-zinc-700 md:flex">
                <a href="{{ route('landing') }}#industries" class="hover:text-[var(--accent)]">Industries</a>
                <a href="{{ route('landing') }}#features" class="hover:text-[var(--accent)]">Features</a>
                <a href="{{ route('landing') }}#pricing" class="hover:text-[var(--accent)]">Pricing</a>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('login') }}" class="hidden rounded-lg border border-zinc-300 px-4 py-2 text-sm font-black text-black no-underline sm:inline-flex">Login</a>
                <a href="{{ route('register.account') }}" class="rounded-lg px-4 py-2 text-sm font-black text-white no-underline" style="background:var(--accent)">Start Free Trial</a>
            </div>
        </nav>
    </header>

    <section class="relative overflow-hidden px-5 py-12 text-white md:py-16" style="background:var(--dark)">
        <div class="absolute inset-y-0 right-0 hidden w-1/2 opacity-35 lg:block">
            <img src="{{ asset('images/people-industry-mosaic.png') }}" alt="{{ $industry['industry'] }} teams using Bama" class="h-full w-full object-cover">
        </div>
        <div class="relative mx-auto grid max-w-7xl gap-8 lg:grid-cols-[.95fr_.7fr]">
            <div class="max-w-3xl">
                <a href="{{ route('landing') }}#industries" class="text-sm font-black uppercase text-white/70 no-underline hover:text-white">Back to industries</a>
                <p class="bama-eyebrow mt-8">Industry Solution</p>
                <h1 class="mt-4 text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">{{ $industry['industry'] }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-white/82">{{ $industry['description'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register.account') }}" class="rounded-full bg-white px-8 py-4 text-center text-sm font-black uppercase text-black no-underline">Start Free Trial</a>
                </div>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/[.08] p-5 shadow-2xl backdrop-blur">
                <p class="text-xs font-black uppercase text-white/60">Workspace includes</p>
                <div class="mt-4 grid gap-2">
                    @foreach($modules->take(8) as $module)
                        <div class="flex items-center gap-3 rounded-lg bg-white/[.08] px-3 py-2">
                            <i class="bi bi-check2-circle" style="color:var(--accent)"></i>
                            <span class="font-bold">{{ $module }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-12">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-[.36fr_1fr]">
            <div>
                <p class="bama-eyebrow">Operating fit</p>
                <h2 class="mt-3 text-3xl font-black">What this workspace helps you control</h2>
                <p class="mt-4 leading-7 text-zinc-600">Bama provisions practical screens, permissions, dashboards, reports, and workflows around the way {{ strtolower($industry['industry']) }} teams actually work.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($features->take(6) as $feature)
                    <article class="bama-card p-4">
                        <span class="grid h-10 w-10 place-items-center rounded-lg text-white" style="background:var(--accent)"><i class="bi {{ $featureIcon($feature) }}"></i></span>
                        <h3 class="mt-4 font-black">{{ $feature }}</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">Track activity, responsibility, status, and performance from one controlled dashboard.</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#F7F8F5] px-5 py-12">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="bama-card p-5">
                    <h2 class="text-xl font-black">Sub-industries</h2>
                    <div class="mt-4 grid gap-3">
                        @foreach($subIndustries->take(6) as $sub)
                            <div class="rounded-lg border border-zinc-100 bg-[#F7F8F5] p-3">
                                <strong>{{ $sub['name'] ?? 'Specialization' }}</strong>
                                <p class="mt-1 text-sm leading-6 text-zinc-600">{{ $sub['description'] ?? $industry['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bama-card p-5">
                    <h2 class="text-xl font-black">Workflows</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($workflows->take(12) as $workflow)
                            <span class="bama-chip px-3 py-2 text-sm">{{ $workflow }}</span>
                        @endforeach
                    </div>
                    <h2 class="mt-6 text-xl font-black">Reports</h2>
                    <div class="mt-4 grid gap-2">
                        @foreach($reports->take(6) as $report)
                            <span class="rounded-lg border border-zinc-100 bg-[#F7F8F5] px-3 py-2 text-sm font-bold">{{ $report }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="bama-card p-5">
                    <h2 class="text-xl font-black">Roles and menus</h2>
                    <div class="mt-4 grid gap-2">
                        @foreach(($roles->isNotEmpty() ? $roles : collect([$industry['industry'].' Admin', $industry['industry'].' Manager', 'Team Member']))->take(6) as $role)
                            <span class="rounded-lg border border-zinc-100 bg-[#F7F8F5] px-3 py-2 text-sm font-bold">{{ $role }}</span>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach($menus->take(10) as $menu)
                            <span class="rounded-full px-3 py-1 text-xs font-black text-white" style="background:var(--accent)">{{ $menu }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 py-12">
        <div class="mx-auto max-w-7xl rounded-lg p-7 text-white md:p-10" style="background:var(--dark)">
            <div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <p class="bama-eyebrow">Ready to operate</p>
                    <h2 class="mt-3 text-3xl font-black">Launch a {{ $industry['industry'] }} workspace with Bama</h2>
                    <p class="mt-3 max-w-2xl leading-7 text-white/75">Start with guided onboarding, then add users, permissions, modules, documents, finance, and reports as your operation grows.</p>
                </div>
                <a href="{{ route('register.account') }}" class="rounded-full bg-white px-8 py-4 text-center text-sm font-black uppercase text-black no-underline">Start Free Trial</a>
            </div>
        </div>
    </section>
</main>
@endsection
