@extends('layouts.marketing', ['title' => $marketingPage->meta_title ?: $marketingPage->title, 'metaDescription' => $marketingPage->meta_description])

@php
    $modules = collect($industry['modules'] ?? []);
    $features = collect($industry['features'] ?? []);
    $subIndustries = collect($industry['sub_industries'] ?? []);
    $workflows = collect($industry['workflows'] ?? []);
    $reports = collect($industry['reports'] ?? []);
    $roles = collect($industry['roles'] ?? []);
    $menus = collect($industry['menus'] ?? $industry['dashboard']['menu_structure'] ?? [])->map(fn ($menu) => is_array($menu) ? ($menu['label'] ?? $menu['module'] ?? 'Module') : $menu);


    $accent = ['#00A651', '#071B12'];
    $mosaicImageSrcset = implode(', ', [
        asset('images/optimized/people-industry-mosaic-640.webp').' 640w',
        asset('images/optimized/people-industry-mosaic-960.webp').' 960w',
        asset('images/optimized/people-industry-mosaic-1254.webp').' 1254w',
    ]);
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
@php
    $defaults = \App\Models\MarketingPage::defaultSections('home');
    $marketingSiteContent = $marketingSiteContent ?? (\App\Models\MarketingPage::resolve('home')->sections ?: $defaults);
    $brand = array_replace($defaults['brand'], (array) data_get($marketingSiteContent, 'brand', []));
    $headerContent = array_replace($defaults['header'], (array) data_get($marketingSiteContent, 'header', []));
    $footerContent = array_replace($defaults['footer'], (array) data_get($marketingSiteContent, 'footer', []));
    $brandLogoUrl = \App\Support\PublicUpload::url(data_get($brand, 'logo_path')) ?: \App\Support\PublicUpload::url('logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png') ?: asset('images/bama-solutions-02.png');
    $brandAlt = data_get($brand, 'logo_alt', 'Bama Solutions');
    $headerLinks = data_get($headerContent, 'nav_links', $defaults['header']['nav_links']);
    $footerColumns = data_get($footerContent, 'columns', $defaults['footer']['columns']);
    $marketingUrl = function (?string $url, string $fallback = '#'): string {
        $url = trim((string) $url);

        if ($url === '') {
            return $fallback;
        }

        if (str_starts_with($url, '#')) {
            return route('landing').$url;
        }

        return $url;
    };
@endphp
<main class="bama-page min-h-screen" style="--accent: {{ $accent[0] }}; --dark: {{ $accent[1] }};">
    @include('landing.partials.site-header')

    <section class="relative overflow-hidden px-5 py-12 text-white md:py-16" style="background:var(--dark)">
        <div class="relative mx-auto grid max-w-7xl gap-8 lg:grid-cols-[.95fr_.7fr]">
            <div class="min-w-0 max-w-3xl">
                <a href="{{ route('landing') }}#industries" class="text-sm font-black uppercase text-white/70 no-underline hover:text-white">{{ data_get($pageSections, 'copy.back_label', 'Back to industries') }}</a>
                <p class="bama-eyebrow mt-8">{{ data_get($industry, 'hero.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">{{ data_get($industry, 'hero.title') }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-white/82">{{ data_get($industry, 'hero.body') }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ data_get($pageSections, 'copy.button_url', route('register.account')) }}" class="rounded-full bg-white px-8 py-4 text-center text-sm font-black uppercase text-black no-underline">{{ data_get($pageSections, 'copy.button_label', 'Start Free Trial') }}</a>
                </div>
                <div class="bama-media-frame mt-8">
                    <picture>
                        @if(data_get($industry, 'media.hero_image_path') === 'images/people-industry-mosaic.png')
                            <source type="image/webp" srcset="{{ $mosaicImageSrcset }}" sizes="(min-width: 1024px) 50vw, 100vw">
                        @endif
                        <img
                            src="{{ \App\Support\PublicUpload::url(data_get($industry, 'media.hero_image_path')) ?: asset('images/people-industry-mosaic.png') }}"
                            alt="{{ data_get($industry, 'media.hero_image_alt') }}"
                            width="1600" height="900"
                            fetchpriority="high" decoding="async"
                        >
                    </picture>
                </div>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/[.08] p-5 shadow-2xl backdrop-blur">
                <p class="text-xs font-black uppercase text-white/60">{{ data_get($pageSections, 'copy.modules_heading', 'Workspace includes') }}</p>
                <div class="mt-4 grid gap-2">
                    @foreach($modules as $module)
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
                <p class="bama-eyebrow">{{ data_get($pageSections, 'copy.fit_eyebrow', 'Operating fit') }}</p>
                <h2 class="mt-3 text-3xl font-black">{{ data_get($pageSections, 'copy.fit_title', 'What this workspace helps you control') }}</h2>
                <p class="mt-4 leading-7 text-zinc-600">
                    {{ data_get($pageSections, 'copy.fit_body', $industry['description']) }}
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($features as $feature)
                    <article class="bama-card p-4">
                        <span class="grid h-10 w-10 place-items-center rounded-lg text-white" style="background:var(--accent)"><i class="bi {{ $featureIcon($feature) }}"></i></span>
                        <h3 class="mt-4 font-black">{{ $feature }}</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">{{ data_get($pageSections, 'copy.feature_body', 'Track activity, responsibility, status, and performance from one controlled dashboard.') }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#F7F8F5] px-5 py-12">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="bama-card p-5">
                    <h2 class="text-xl font-black">{{ data_get($pageSections, 'copy.sub_industries_heading', 'Sub-industries') }}</h2>
                    <div class="mt-4 grid gap-3">
                        @foreach($subIndustries as $sub)
                            <div class="rounded-lg border border-zinc-100 bg-[#F7F8F5] p-3">
                                <strong>{{ $sub['name'] ?? 'Specialization' }}</strong>
                                <p class="mt-1 text-sm leading-6 text-zinc-600">{{ $sub['description'] ?? $industry['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="bama-card p-5">
                    <h2 class="text-xl font-black">{{ data_get($pageSections, 'copy.workflows_heading', 'Workflows') }}</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($workflows as $workflow)
                            <span class="bama-chip px-3 py-2 text-sm">{{ $workflow }}</span>
                        @endforeach
                    </div>
                    <h2 class="mt-6 text-xl font-black">{{ data_get($pageSections, 'copy.reports_heading', 'Reports') }}</h2>
                    <div class="mt-4 grid gap-2">
                        @foreach($reports as $report)
                            <span class="rounded-lg border border-zinc-100 bg-[#F7F8F5] px-3 py-2 text-sm font-bold">{{ $report }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="bama-card p-5">
                    <h2 class="text-xl font-black">{{ data_get($pageSections, 'copy.roles_heading', 'Roles and menus') }}</h2>
                    <div class="mt-4 grid gap-2">
                        @foreach($roles as $role)
                            <span class="rounded-lg border border-zinc-100 bg-[#F7F8F5] px-3 py-2 text-sm font-bold">{{ $role }}</span>
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach($menus as $menu)
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
                    <p class="bama-eyebrow">{{ data_get($pageSections, 'copy.cta_eyebrow', 'Ready to operate') }}</p>
                    <h2 class="mt-3 text-3xl font-black">{{ data_get($pageSections, 'copy.cta_title', 'Launch a '.$industry['industry'].' workspace with Bama') }}</h2>
                    <p class="mt-3 max-w-2xl leading-7 text-white/75">{{ data_get($pageSections, 'copy.cta_body', 'Start with guided onboarding, then add users, permissions, modules, documents, finance, and reports as your operation grows.') }}</p>
                </div>
                <a href="{{ data_get($pageSections, 'copy.button_url', route('register.account')) }}" class="rounded-full bg-white px-8 py-4 text-center text-sm font-black uppercase text-black no-underline">{{ data_get($pageSections, 'copy.button_label', 'Start Free Trial') }}</a>
            </div>
        </div>
    </section>
    @include('landing.partials.page-blocks', ['blocks' => $pageSections['blocks'] ?? []])
    @include('landing.partials.site-footer')
</main>
@endsection
