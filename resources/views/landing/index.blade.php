@extends('layouts.marketing', [
    'title' => $marketingPage?->meta_title ?: 'Bama Business Cloud',
    'metaDescription' => $marketingPage?->meta_description,
])

@php
    $content = $marketingContent ?? \App\Models\MarketingPage::defaultSections('home');
    $defaults = \App\Models\MarketingPage::defaultSections('home');
    $brand = array_replace_recursive($defaults['brand'], (array) data_get($content, 'brand', []));
    $headerContent = array_replace($defaults['header'], (array) data_get($content, 'header', []));
    $hero = array_replace($defaults['hero'], (array) data_get($content, 'hero', []));
    $media = array_replace($defaults['media'], (array) data_get($content, 'media', []));
    $stats = data_get($content, 'stats', []);
    $insight = array_replace($defaults['insight'], (array) data_get($content, 'insight', []));
    $trust = array_replace($defaults['trust'], (array) data_get($content, 'trust', []));
    $featuresContent = array_replace($defaults['features'], (array) data_get($content, 'features', []));
    $industriesSection = array_replace($defaults['industries_section'], (array) data_get($content, 'industries_section', []));
    $benefitsContent = array_replace($defaults['benefits'], (array) data_get($content, 'benefits', []));
    $stepsContent = array_replace($defaults['steps'], (array) data_get($content, 'steps', []));
    $showcaseContent = array_replace($defaults['showcase'], (array) data_get($content, 'showcase', []));
    $pricingContent = array_replace($defaults['pricing'], (array) data_get($content, 'pricing', []));
    $testimonialsContent = array_replace($defaults['testimonials'], (array) data_get($content, 'testimonials', []));
    $faqContent = array_replace($defaults['faq'], (array) data_get($content, 'faq', []));
    $finalCta = array_replace($defaults['final_cta'], (array) data_get($content, 'final_cta', []));
    $footerContent = array_replace($defaults['footer'], (array) data_get($content, 'footer', []));
    $extraBlocks = data_get($content, 'blocks', []);
    $brandLogoUrl = \App\Support\PublicUpload::url(data_get($brand, 'logo_path')) ?: \App\Support\PublicUpload::url('logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png') ?: asset('images/bama-solutions-02.png');
    $brandAlt = data_get($brand, 'logo_alt', 'Bama Solutions');
    $headerLinks = data_get($headerContent, 'nav_links', $defaults['header']['nav_links']);
    $footerColumns = data_get($footerContent, 'columns', $defaults['footer']['columns']);
    $trustLogos = data_get($trust, 'logos', $defaults['trust']['logos']);
    $trustBadges = data_get($trust, 'badges', $defaults['trust']['badges']);
    $headerLinks = is_array($headerLinks) ? $headerLinks : [];
    $footerColumns = is_array($footerColumns) ? $footerColumns : [];
    $trustLogos = is_array($trustLogos) ? $trustLogos : [];
    $trustBadges = is_array($trustBadges) ? $trustBadges : [];

    $coreModules = data_get($featuresContent, 'modules', $defaults['features']['modules']);
    $coreModules = is_array($coreModules) ? $coreModules : [];

    $moduleColors = [
        ['#00A651', '#EAF8F0'],
        ['#F59E0B', '#FFF7E6'],
        ['#2563EB', '#EFF6FF'],
        ['#EF4444', '#FEF2F2'],
        ['#7C3AED', '#F5F3FF'],
        ['#0891B2', '#ECFEFF'],
        ['#DB2777', '#FDF2F8'],
        ['#16A34A', '#F0FDF4'],
        ['#EA580C', '#FFF7ED'],
        ['#4F46E5', '#EEF2FF'],
    ];

    $benefitItems = data_get($benefitsContent, 'items', $defaults['benefits']['items']);
    $stepItems = data_get($stepsContent, 'items', $defaults['steps']['items']);
    $testimonialItems = data_get($testimonialsContent, 'items', $defaults['testimonials']['items']);
    $faqItems = data_get($faqContent, 'items', $defaults['faq']['items']);
    $benefitItems = is_array($benefitItems) ? $benefitItems : [];
    $stepItems = is_array($stepItems) ? $stepItems : [];
    $testimonialItems = is_array($testimonialItems) ? $testimonialItems : [];
    $faqItems = is_array($faqItems) ? $faqItems : [];
    $showcase = collect(data_get($showcaseContent, 'tabs', $defaults['showcase']['tabs']))
        ->mapWithKeys(fn ($tab) => [
            (string) (is_array($tab) ? ($tab['name'] ?? 'Dashboard') : $tab) => array_values(is_array($tab) ? ($tab['items'] ?? []) : []),
        ])
        ->filter(fn ($items, $name) => $name !== '' && count($items) > 0)
        ->all();

    $industryCards = collect($industries ?? [])->values();
    $industryPreview = $industryCards->mapWithKeys(fn ($industry) => [
        $industry['slug'] => [
            'name' => $industry['name'],
            'description' => $industry['description'],
            'modules' => array_slice($industry['modules'] ?? [], 0, 8),
            'features' => array_slice($industry['features'] ?? [], 0, 4),
            'dashboard_features' => array_slice($industry['dashboard_features'] ?? [], 0, 6),
            'sub_industries' => collect($industry['sub_industries'] ?? [])->pluck('name')->take(4)->values(),
            'menus' => array_slice($industry['menu_structure'] ?? [], 0, 8),
            'reports' => array_slice($industry['reports'] ?? [], 0, 5),
            'workflows' => array_slice($industry['workflows'] ?? [], 0, 5),
            'templates' => array_slice($industry['templates'] ?? [], 0, 5),
            'roles' => array_slice($industry['roles'] ?? [], 0, 5),
            'permissions_count' => count($industry['permissions'] ?? []),
            'core_modules_count' => count($industry['core_modules'] ?? []),
        ],
    ]);
    $heroImagePath = data_get($media, 'hero_image_path', $defaults['media']['hero_image_path']);
    $insightImagePath = data_get($media, 'insight_image_path', $defaults['media']['insight_image_path']);
    $featuresImagePath = data_get($media, 'features_image_path', $defaults['media']['features_image_path']);
    $heroImageUrl = \App\Support\PublicUpload::url($heroImagePath) ?: asset('images/hero-green-team.png');
    $insightImageUrl = \App\Support\PublicUpload::url($insightImagePath) ?: asset('images/people-industry-mosaic.png');
    $featuresImageUrl = \App\Support\PublicUpload::url($featuresImagePath) ?: asset('images/people-industry-mosaic.png');
    $heroImageSrcset = $heroImagePath === $defaults['media']['hero_image_path'] ? implode(', ', [
        asset('images/optimized/hero-green-team-960.webp').' 960w',
        asset('images/optimized/hero-green-team-1280.webp').' 1280w',
        asset('images/optimized/hero-green-team-1672.webp').' 1672w',
    ]) : null;
    $insightImageSrcset = $insightImagePath === $defaults['media']['insight_image_path'] ? implode(', ', [
        asset('images/optimized/people-industry-mosaic-640.webp').' 640w',
        asset('images/optimized/people-industry-mosaic-960.webp').' 960w',
        asset('images/optimized/people-industry-mosaic-1254.webp').' 1254w',
    ]) : null;
    $featuresImageSrcset = $featuresImagePath === $defaults['media']['features_image_path'] ? implode(', ', [
        asset('images/optimized/people-industry-mosaic-640.webp').' 640w',
        asset('images/optimized/people-industry-mosaic-960.webp').' 960w',
        asset('images/optimized/people-industry-mosaic-1254.webp').' 1254w',
    ]) : null;
@endphp

@section('body')
<style>
    @font-face {
        font-family: 'tt_normsregular';
        src:
            local('tt_normsregular'),
            local('TT Norms Regular'),
            local('TT Norms');
        font-weight: 400 900;
        font-style: normal;
        font-display: swap;
    }

    @font-face {
        font-family: 'McQueen';
        src:
            local('McQueen SemiBold'),
            local('McQueen 600'),
            local('McQueen');
        font-weight: 600;
        font-style: normal;
        font-display: swap;
    }

    .home-page {
        --green: #00A651;
        --green-dark: #007A3B;
        --green-soft: #EAF8F0;
        --black: #000000;
        --line: #e5e7eb;
        --muted: #000000;
        --page: #F7F8F5;
        --font-brand: 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        --font-heading: 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        --font-display: var(--bama-font-display, 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif);
        font-family: var(--font-brand) !important;
        font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
        font-optical-sizing: auto;
        font-synthesis-weight: none;
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .home-page h1,
    .home-page h2,
    .home-page h3,
    .home-page h4,
    .home-page h5,
    .home-page h6 {
        font-family: var(--font-heading) !important;
        font-weight: 700 !important;
        font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
        letter-spacing: -0.02em;
        line-height: 1.08;
        text-rendering: geometricPrecision;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .home-page p,
    .home-page a,
    .home-page button,
    .home-page span,
    .home-page summary,
    .home-page input {
        font-family: var(--font-brand) !important;
        font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .home-page .text-zinc-400,
    .home-page .text-zinc-500,
    .home-page .text-zinc-600,
    .home-page .text-zinc-700 {
        color: #000000 !important;
    }

    .home-page .bg-black .text-zinc-400,
    .home-page .dark-panel .text-zinc-400 {
        color: #a1a1aa !important;
    }

    .home-page .bg-black .text-zinc-500,
    .home-page .dark-panel .text-zinc-500 {
        color: #71717a !important;
    }

    .home-page .font-semibold,
    .home-page .font-bold,
    .home-page .font-black {
        font-weight: 500 !important;
    }

    .home-page h1,
    .home-page h2,
    .home-page h3,
    .home-page h4,
    .home-page h5,
    .home-page h6 {
        font-weight: 600 !important;
    }

    .home-page .hero-field { background: #000000; }

    .home-page .hero-image-clear {
        opacity: 1;
        mix-blend-mode: normal;
        filter: brightness(1.18) contrast(1.12) saturate(1.08);
    }

    .home-page .eyebrow {
        color: var(--green);
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .home-page .panel {
        border: 1px solid rgba(0, 0, 0, .09);
        background: rgba(255, 255, 255, .96);
        border-radius: 18px;
        box-shadow: 0 18px 48px rgba(15, 23, 42, .08);
    }

    .home-page .soft-panel {
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff, #fbfcfa);
        box-shadow: 0 10px 30px rgba(15, 23, 42, .045);
    }

    .home-page .testimonial-showcase {
        background: #f2f8f4;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(15, 23, 42, .08);
    }

    .home-page .testimonial-quote-panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: clamp(2rem, 4vw, 4.5rem) clamp(1.5rem, 4vw, 4rem);
        background: rgba(255, 255, 255, .18);
    }

    .home-page .testimonial-quote-mark {
        color: #00A651;
        font-size: clamp(3.2rem, 6vw, 5rem);
        line-height: 0.7;
        font-weight: 900;
    }

    .home-page .testimonial-quote {
        margin-top: 1rem;
        color: #0f172a;
        font-size: clamp(1.7rem, 2.3vw, 3.3rem);
        line-height: 1.12;
        letter-spacing: -0.05em;
        font-weight: 500;
    }

    .home-page .testimonial-author {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 2rem;
    }

    .home-page .testimonial-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(15, 23, 42, .08);
    }

    .home-page .testimonial-author-name {
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 900;
        margin: 0;
    }

    .home-page .testimonial-author-role {
        margin: 0.25rem 0 0;
        color: rgba(15, 23, 42, .72);
        font-size: 1rem;
    }

    .home-page .testimonial-media-wrap {
        position: relative;
        min-height: clamp(300px, 38vw, 520px);
        padding: 1rem 1rem 1rem 0;
        display: flex;
        align-items: end;
        justify-content: center;
    }

    .home-page .testimonial-media {
        position: relative;
        display: block;
        width: min(100%, 760px);
        height: 100%;
        min-height: clamp(300px, 38vw, 520px);
        max-height: 520px;
        border-radius: 26px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .14);
        background: #dfe7df;
    }

    .home-page .testimonial-media img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: saturate(0.9) contrast(1.02);
    }

    .home-page .testimonial-accent {
        position: absolute;
        right: 0;
        top: 0;
        width: 30%;
        height: 100%;
        background: linear-gradient(180deg, rgba(1, 128, 72, 0.12), rgba(1, 128, 72, 0));
        pointer-events: none;
    }

    .home-page .testimonial-accent::before,
    .home-page .testimonial-accent::after {
        content: "";
        position: absolute;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #00A651;
        box-shadow: 0 0 0 8px rgba(0, 166, 81, 0.1);
    }

    .home-page .testimonial-accent::before {
        top: 10%;
        right: 18%;
    }

    .home-page .testimonial-accent::after {
        bottom: 7%;
        right: 16%;
        background: #007A3B;
        box-shadow: 0 0 0 8px rgba(0, 122, 59, 0.12);
    }

    .home-page .testimonial-badge {
        position: absolute;
        right: 1rem;
        bottom: 1rem;
        max-width: min(72%, 300px);
        padding: 1rem 1.1rem .9rem;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(2px);
        box-shadow: 0 18px 32px rgba(15, 23, 42, .15);
    }

    .home-page .testimonial-badge strong {
        display: block;
        font-size: clamp(1.2rem, 2vw, 2.2rem);
        line-height: 1.1;
        color: #0f172a;
    }

    .home-page .testimonial-badge span {
        display: block;
        margin-top: .25rem;
        font-size: .8rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: rgba(15, 23, 42, .7);
    }

    .home-page .dark-panel {
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 18px;
        background:
            radial-gradient(circle at 20% 20%, rgba(0, 166, 81, .23), transparent 34%),
            #000000;
        color: #ffffff;
    }

    .home-page .btn-primary {
        background: var(--green);
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(0, 166, 81, .2);
    }

    .home-page .section-pad {
        padding-block: clamp(2.8rem, 4.5vw, 4.1rem);
    }

    .home-page .section-title {
        font-size: clamp(1.9rem, 3vw, 2.55rem);
        line-height: 1.05;
    }

    .home-page .hero-title {
        font-family: var(--font-display) !important;
        font-size: clamp(2.15rem, 3.8vw, 3.75rem);
        font-weight: 900 !important;
        line-height: .98;
        letter-spacing: 0;
    }

    .home-page .bama-animate {
        opacity: 0;
        transform: translate3d(0, 18px, 0);
        transition:
            opacity .7s cubic-bezier(.2, .7, .2, 1),
            transform .7s cubic-bezier(.2, .7, .2, 1),
            border-color .2s ease,
            box-shadow .2s ease;
        transition-delay: var(--bama-delay, 0ms);
        will-change: opacity, transform;
    }

    .home-page .bama-animate.is-visible {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }

    .home-page .hero-title,
    .home-page .hero-field .eyebrow,
    .home-page .hero-field .btn-primary,
    .home-page .hero-field .btn-primary + a {
        animation: bama-rise .72s cubic-bezier(.2, .7, .2, 1) both;
    }

    .home-page .hero-field .eyebrow { animation-delay: .04s; }
    .home-page .hero-title { animation-delay: .12s; }
    .home-page .hero-field .btn-primary { animation-delay: .22s; }
    .home-page .hero-field .btn-primary + a { animation-delay: .28s; }

    .home-page .panel,
    .home-page .soft-panel,
    .home-page .industry-tab,
    .home-page [data-product-tab] {
        transition:
            transform .22s ease,
            border-color .22s ease,
            box-shadow .22s ease,
            background-color .22s ease;
    }

    .home-page .panel:hover,
    .home-page .soft-panel:hover,
    .home-page .industry-tab:hover,
    .home-page [data-product-tab]:hover {
        transform: translateY(-3px);
    }

    .home-page .dark-panel {
        animation: bama-float 8s ease-in-out infinite;
    }

    .home-page .bama-bar {
        transform-origin: bottom;
        animation: bama-bar-grow .9s cubic-bezier(.2, .7, .2, 1) both;
        animation-delay: var(--bama-delay, 0ms);
    }

    .home-page .bama-progress {
        transform-origin: left;
        animation: bama-progress-grow .85s cubic-bezier(.2, .7, .2, 1) both;
        animation-delay: var(--bama-delay, 0ms);
    }

    .home-page .trust-logo-viewport {
        overflow: hidden;
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
    }

    .home-page .trust-logo-track {
        display: flex;
        width: max-content;
        gap: .95rem;
        animation: bama-logo-slide 32s linear infinite;
    }

    .home-page .trust-logo-track:hover {
        animation-play-state: paused;
    }

    .home-page .trust-logo-card {
        display: grid;
        place-items: center;
        width: clamp(172px, 18vw, 244px);
        height: 82px;
        flex: 0 0 auto;
        border: 1px solid rgba(0, 0, 0, .09);
        border-radius: 12px;
        background: #ffffff;
        padding: 14px 20px;
    }

    .home-page .trust-logo-card img {
        display: block;
        max-width: 100%;
        max-height: 56px;
        object-fit: contain;
    }

    .home-page .trust-logo-fallback {
        color: #111827;
        font-size: .96rem;
        font-weight: 900;
        text-align: center;
    }

    .home-page .industry-preview.is-collapsed .industry-preview-extra {
        display: none;
    }

    .home-page .industry-preview.is-collapsed #industrySummary {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .home-page .industry-preview.is-collapsed .industry-preview-summary-grid {
        grid-template-columns: 1fr;
    }

    .home-page .industry-preview.is-collapsed #industryModules > :nth-child(n+7),
    .home-page .industry-preview.is-collapsed #industryDashboards > :nth-child(n+3) {
        display: none;
    }

    .home-page .industry-preview-toggle i {
        transition: transform .2s ease;
    }

    .home-page .industry-preview:not(.is-collapsed) .industry-preview-toggle i {
        transform: rotate(180deg);
    }

    @keyframes bama-rise {
        from { opacity: 0; transform: translate3d(0, 16px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }

    @keyframes bama-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    @keyframes bama-bar-grow {
        from { transform: scaleY(.18); opacity: .55; }
        to { transform: scaleY(1); opacity: 1; }
    }

    @keyframes bama-progress-grow {
        from { transform: scaleX(.18); opacity: .55; }
        to { transform: scaleX(1); opacity: 1; }
    }

    @keyframes bama-logo-slide {
        from { transform: translate3d(0, 0, 0); }
        to { transform: translate3d(calc(-50% - .475rem), 0, 0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .home-page *,
        .home-page *::before,
        .home-page *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
        }

        .home-page .bama-animate {
            opacity: 1;
            transform: none;
        }

        .home-page .trust-logo-viewport {
            overflow-x: auto;
            -webkit-mask-image: none;
            mask-image: none;
        }

        .home-page .trust-logo-track {
            animation: none;
        }
    }

    .home-page details summary::-webkit-details-marker { display: none; }
</style>

<main id="top" class="home-page bg-[#F7F8F5] text-black">
    <header class="sticky top-0 z-50 border-b border-zinc-200 bg-[#FBFCFA]/95 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-2">
            <a href="#top" class="flex items-center gap-3">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="block h-auto w-[124px] max-w-[32vw] object-contain" style="width:124px;height:auto;max-width:32vw;object-fit:contain;">
            </a>

            <div class="hidden items-center gap-6 text-sm font-bold text-zinc-700 lg:flex">
                @foreach($headerLinks as $link)
                    @if(is_array($link) && ($link['label'] ?? '') && ($link['url'] ?? ''))
                        @if(strtolower($link['label']) === 'features')
                            <div class="group relative py-2">
                                <a href="{{ $link['url'] }}" class="hover:text-[#00A651]">{{ $link['label'] }}</a>
                                <div class="invisible absolute left-1/2 top-full w-[640px] -translate-x-1/2 rounded-lg border border-zinc-200 bg-white p-4 opacity-0 shadow-2xl transition group-hover:visible group-hover:opacity-100">
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach (array_slice($coreModules, 0, 8) as $module)
                                            @php
                                                $name = is_array($module) ? ($module['name'] ?? $module[0] ?? '') : (string) $module;
                                                $copy = is_array($module) ? ($module['copy'] ?? $module[1] ?? '') : '';
                                            @endphp
                                            <a href="{{ $link['url'] }}" class="rounded-lg p-3 hover:bg-[#EAF8F0]">
                                                <span class="block font-black">{{ $name }}</span>
                                                <span class="mt-1 block text-xs font-medium leading-5 text-zinc-500">{{ $copy }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif(strtolower($link['label']) === 'industries' && $industryCards->isNotEmpty())
                            <div class="group relative py-2">
                                <a href="{{ $link['url'] }}" class="hover:text-[#00A651]">{{ $link['label'] }}</a>
                                <div class="invisible absolute left-1/2 top-full max-h-[70vh] w-[720px] -translate-x-1/2 overflow-y-auto rounded-lg border border-zinc-200 bg-white p-3 opacity-0 shadow-2xl transition group-hover:visible group-hover:opacity-100">
                                    <div class="grid grid-cols-2 gap-1.5">
                                        @foreach ($industryCards as $industry)
                                            @php
                                                $industrySlug = str_replace('_', '-', $industry['slug']);
                                            @endphp
                                            <a href="{{ route('industries.show', ['industry' => $industrySlug]) }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 hover:bg-[#EAF8F0]">
                                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[#EAF8F0] text-xs font-black text-[#007A3B]">{{ substr($industry['name'], 0, 1) }}</span>
                                                <span class="text-[15px] font-semibold text-[#111827]">{{ $industry['name'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $link['url'] }}" class="hover:text-[#00A651]">{{ $link['label'] }}</a>
                        @endif
                    @endif
                @endforeach
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                @if(data_get($headerContent, 'demo_label') && data_get($headerContent, 'demo_url'))
                    <a href="{{ data_get($headerContent, 'demo_url') }}" class="hidden rounded-lg border border-zinc-300 px-4 py-2 text-sm font-black text-zinc-800 hover:border-[#00A651] hover:text-[#00A651] md:inline-flex">{{ data_get($headerContent, 'demo_label') }}</a>
                @endif
                @if(data_get($headerContent, 'cta_label'))
                    <a href="{{ data_get($headerContent, 'cta_url', route('register.account')) }}" class="rounded-lg bg-[#00A651] px-4 py-2 text-sm font-black text-white sm:px-5">{{ data_get($headerContent, 'cta_label', 'Start Free Trial') }}</a>
                @endif
            </div>
        </nav>
    </header>
    <section class="hero-field relative isolate overflow-hidden bg-black px-5 py-12 text-white sm:py-14 lg:min-h-[590px] lg:py-0">
        <picture class="absolute inset-y-0 right-0 z-[-1] hidden h-full lg:block" style="width: 50%; overflow: hidden;">
            @if($heroImageSrcset)
                <source type="image/webp" srcset="{{ $heroImageSrcset }}" sizes="58vw">
            @endif
            <img
                src="{{ $heroImageUrl }}"
                alt="{{ data_get($media, 'hero_image_alt', 'Business leaders using Bama cloud ERP') }}"
                width="1672"
                height="941"
                class="bama-upload-image h-full w-full object-contain"
                fetchpriority="high"
                decoding="async"
            >
        </picture>

        <div class="mx-auto flex max-w-7xl items-center lg:min-h-[590px]">
            <div class="max-w-2xl py-7">
                <p class="text-sm font-black text-[#79D9A3]">{{ data_get($hero, 'eyebrow', 'One Platform to Manage Every Business Operation') }}</p>
                <h1 class="hero-title mt-5 max-w-4xl font-black text-white">
                    {{ data_get($hero, 'title', 'Run Your Entire Business From One Unified Platform') }}
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-white/85">
                    {{ data_get($hero, 'body', 'Manage customers, projects, finances, inventory, operations, and industry-specific workflows from a single cloud platform.') }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ data_get($hero, 'primary_url', route('register.account')) }}" class="rounded-full bg-white px-8 py-4 text-center text-sm font-black uppercase text-black transition hover:bg-[#EAF8F0]">{{ data_get($hero, 'primary_label', 'Start Free Trial') }}</a>
                    @if(data_get($hero, 'secondary_label') && data_get($hero, 'secondary_url'))
                        <a href="{{ data_get($hero, 'secondary_url') }}" class="rounded-full border border-white/40 px-8 py-4 text-center text-sm font-black uppercase text-white transition hover:border-white hover:bg-white/10">{{ data_get($hero, 'secondary_label') }}</a>
                    @endif
                </div>
                <div class="mt-8 grid max-w-[720px] grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($stats ?: [['value' => '90%', 'label' => 'Uptime'], ['value' => '700', 'label' => 'Businesses'], ['value' => 'Millions', 'label' => 'Transactions'], ['value' => 'Secure', 'label' => 'Security']] as $stat)
                        @php
                            $statValue = is_array($stat) ? ($stat['value'] ?? '') : $stat;
                            $statLabel = is_array($stat) ? ($stat['label'] ?? '') : '';
                            $statNumber = preg_replace('/[^0-9.]+/', '', (string) $statValue);
                            $statSuffix = preg_replace('/[0-9.]+/', '', (string) $statValue);
                            $statDecimals = str_contains((string) $statNumber, '.') ? 1 : 0;
                        @endphp
                        <div class="rounded-[18px] border border-white/10 bg-[#1b2420]/90 px-2 py-3 shadow-inner shadow-black/10 backdrop-blur-[2px] sm:px-3">
                            <p
                                class="stat-number text-[0.95rem] font-black leading-none sm:text-[1.08rem]"
                                @if($statNumber !== '')
                                    data-target="{{ $statNumber }}"
                                    data-suffix="{{ $statSuffix }}"
                                    data-decimals="{{ $statDecimals }}"
                                @endif
                            >{{ $statValue }}</p>
                            <p class="mt-2 text-[8px] font-bold uppercase tracking-[0.12em] text-white/60">{{ $statLabel }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-8 overflow-hidden rounded-2xl border border-white/10 bg-[#062515] lg:hidden" style="aspect-ratio: 1672 / 941;">
                    <picture class="block h-full w-full">
                        @if($heroImageSrcset)
                            <source type="image/webp" srcset="{{ $heroImageSrcset }}" sizes="100vw">
                        @endif
                        <img
                            src="{{ $heroImageUrl }}"
                            alt="{{ data_get($media, 'hero_image_alt', 'Business leaders using Bama cloud ERP') }}"
                            width="1672"
                            height="941"
                            class="bama-upload-image h-full w-full object-contain object-center"
                            fetchpriority="high"
                            decoding="async"
                        >
                    </picture>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#F7F8F5] px-5 py-7">
        <div class="mx-auto grid max-w-6xl overflow-hidden rounded-[22px] border border-zinc-200 bg-[#071B12] lg:grid-cols-[.95fr_1fr]">
            <div class="relative flex h-[240px] items-center justify-center bg-[#071B12] p-3 sm:h-[300px] sm:p-4 lg:h-[370px] lg:p-5">
                <picture class="block h-full w-full">
                    @if($insightImageSrcset)
                        <source type="image/webp" srcset="{{ $insightImageSrcset }}" sizes="(min-width: 1024px) 50vw, 100vw">
                    @endif
                    <img
                        src="{{ $insightImageUrl }}"
                        alt="{{ data_get($media, 'insight_image_alt', 'Diverse teams across industries using one business platform') }}"
                        width="1254"
                        height="1254"
                        class="bama-upload-image h-full w-full object-contain object-center"
                        loading="lazy"
                        decoding="async"
                    >
                </picture>
            </div>
            <div class="relative flex flex-col justify-center gap-5 bg-[#071B12] p-7 text-white sm:p-9 lg:p-10">
                <div>
                    <p class="text-xs font-black uppercase text-[#79D9A3]">{{ data_get($insight, 'eyebrow', 'Operational intelligence') }}</p>
                    <h2 class="mt-4 max-w-xl text-3xl font-black leading-tight sm:text-4xl">{{ data_get($insight, 'title', 'See every business signal clearly') }}</h2>
                    <p class="mt-5 max-w-xl text-base leading-7 text-white/80">
                        {{ data_get($insight, 'body', 'Finance, CRM, projects, procurement, inventory, and industry dashboards come together in one connected operating view.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach (data_get($insight, 'bullets', []) ?: [['title' => 'Live KPIs', 'copy' => 'Real-time decisions'], ['title' => 'Unified data', 'copy' => 'One operating view'], ['title' => 'Executive clarity', 'copy' => 'Faster reporting']] as $bullet)
                        <div class="rounded-lg border border-white/15 bg-white/5 px-4 py-3">
                            <p class="text-sm font-black">{{ is_array($bullet) ? ($bullet['title'] ?? '') : $bullet }}</p>
                            <p class="mt-1 text-xs text-white/60">{{ is_array($bullet) ? ($bullet['copy'] ?? '') : '' }}</p>
                        </div>
                    @endforeach
                </div>
                <a href="{{ data_get($insight, 'button_url', '#solutions') }}" class="inline-flex w-fit rounded-lg border border-white/70 px-5 py-3 text-sm font-black text-white transition hover:border-[#79D9A3] hover:text-[#79D9A3]">{{ data_get($insight, 'button_label', 'Explore dashboards') }}</a>
            </div>
        </div>
    </section>

    <section class="bg-[#F7F8F5] px-5 py-8">
        <div class="mx-auto max-w-7xl">
            <p class="text-center text-sm font-bold text-zinc-600">{{ data_get($trust, 'heading', 'Trusted by organizations across multiple industries') }}</p>
            <div class="trust-logo-viewport mt-5" aria-label="Trusted organization logos">
                <div class="trust-logo-track">
                    @for ($pass = 0; $pass < 2; $pass++)
                        @foreach ($trustLogos as $logo)
                            @php
                                $label = is_array($logo) ? ($logo['label'] ?? $logo['alt'] ?? 'Trusted organization') : (string) $logo;
                                $src = is_array($logo) ? ($logo['src'] ?? $logo['image'] ?? null) : null;

                                if (! $src && is_string($logo) && preg_match('/\.(svg|png|jpe?g|webp|gif)(\?.*)?$/i', $logo)) {
                                    $src = $logo;
                                }

                                $logoUrl = null;

                                if ($src) {
                                    $logoUrl = preg_match('/^(https?:)?\/\//i', $src) || str_starts_with($src, 'data:')
                                        ? $src
                                        : (\App\Support\PublicUpload::url($src) ?: asset(ltrim($src, '/')));
                                }
                            @endphp
                            <div class="trust-logo-card" @if($pass === 1) aria-hidden="true" @endif>
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $pass === 0 ? $label : '' }}" loading="lazy">
                                @else
                                    <span class="trust-logo-fallback">{{ $label }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                @foreach ($trustBadges as $badge)
                    <span class="rounded-full bg-[#EAF8F0] px-4 py-2 text-xs font-black uppercase text-[#007A3B]">{{ $badge }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <section id="features" class="bg-[#F7F8F5] px-5 py-8">
        <div class="mx-auto max-w-6xl">
            <div class="grid gap-6 lg:grid-cols-[.42fr_1fr]">
                <div>
                    <p class="eyebrow">{{ data_get($featuresContent, 'eyebrow', 'Core platform') }}</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight lg:text-3xl">{{ data_get($featuresContent, 'title', 'Everything Your Business Needs') }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-600">
                        {{ data_get($featuresContent, 'body', 'A complete operating suite for CRM, finance, accounting, projects, inventory, procurement, HR, documents, reporting, and portal workflows.') }}
                    </p>
                    <div class="bama-media-frame mt-4 border border-zinc-200 shadow-sm">
                        <picture class="block">
                            @if($featuresImageSrcset)
                                <source type="image/webp" srcset="{{ $featuresImageSrcset }}" sizes="(min-width: 1024px) 34vw, 100vw">
                            @endif
                            <img
                                src="{{ $featuresImageUrl }}"
                                alt="{{ data_get($media, 'features_image_alt', 'Diverse teams using the platform') }}"
                                width="1254"
                                height="1254"
                                class="bama-upload-image"
                                loading="lazy"
                                decoding="async"
                            >
                        </picture>
                    </div>
                </div>
                <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($coreModules as $module)
                        @php
                            $name = is_array($module) ? ($module['name'] ?? $module[0] ?? '') : (string) $module;
                            $copy = is_array($module) ? ($module['copy'] ?? $module[1] ?? '') : '';
                            $icon = is_array($module) ? ($module['icon'] ?? $module[2] ?? substr($name, 0, 1)) : substr($name, 0, 1);
                            [$moduleColor, $moduleSoftColor] = $moduleColors[$loop->index % count($moduleColors)];
                        @endphp
                        @continue($name === '')
                        <article class="soft-panel p-3 transition hover:-translate-y-1 hover:shadow-lg" style="border-color: {{ $loop->first ? $moduleColor : 'rgba(0, 0, 0, .08)' }};">
                            <span class="grid h-8 w-8 place-items-center rounded-md text-xs font-black text-white" style="background-color: {{ $moduleColor }};">{{ $icon }}</span>
                            <h3 class="mt-3 text-sm font-black">{{ $name }}</h3>
                            <p class="mt-1.5 text-xs leading-5 text-zinc-600">{{ $copy }}</p>
                            <div class="mt-3 rounded-md border border-zinc-200 p-2" style="background-color: {{ $moduleSoftColor }};">
                                <div class="h-1.5 w-2/3 rounded-full" style="background-color: {{ $moduleColor }};"></div>
                                <div class="mt-2 grid grid-cols-3 gap-1.5">
                                    <span class="h-5 rounded bg-zinc-100"></span>
                                    <span class="h-5 rounded bg-zinc-100"></span>
                                    <span class="h-5 rounded bg-zinc-100"></span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="industries" class="section-pad bg-[#FBFCFA] px-5">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="eyebrow">{{ data_get($industriesSection, 'eyebrow', 'Industry solutions') }}</p>
                <h2 class="section-title mt-3 font-black">{{ data_get($industriesSection, 'title', 'Built for the way your industry operates') }}</h2>
                <p class="mt-4 text-base leading-7 text-zinc-600">
                    {{ data_get($industriesSection, 'body', 'Choose an industry to preview the modules, sub-industries, and dashboard features available during workspace setup.') }}
                </p>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[.9fr_1.1fr]">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($industryCards as $industry)
                        <a href="{{ route('industries.show', ['industry' => str_replace('_', '-', $industry['slug'])]) }}" data-industry-tab="{{ $industry['slug'] }}" class="industry-tab rounded-2xl border border-zinc-200 bg-white p-4 text-left text-black no-underline shadow-sm transition hover:-translate-y-1 hover:border-[#00A651] hover:text-black hover:shadow-xl">
                            <div class="flex items-start justify-between gap-4">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-[#EAF8F0] text-sm font-black text-[#007A3B]">{{ substr($industry['name'], 0, 1) }}</span>
                                <span class="text-sm font-black text-[#00A651]">Learn More</span>
                            </div>
                            <h3 class="mt-4 text-lg font-black">{{ $industry['name'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $industry['description'] }}</p>
                        </a>
                    @endforeach
                </div>

                <div class="industry-preview is-collapsed sticky top-20 h-max rounded-[22px] bg-black p-5 text-white" data-industry-preview>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase text-[#8BE7B6]">Industry workspace preview</p>
                            <h3 id="industryTitle" class="mt-2 text-2xl font-black">Construction</h3>
                        </div>
                        <button type="button" class="industry-preview-toggle inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/[.08] text-white" data-industry-preview-toggle aria-expanded="false" aria-label="Expand industry preview">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                    <p id="industrySummary" class="mt-3 leading-7 text-zinc-300"></p>

                    <div class="industry-preview-summary-grid mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-white/10 bg-white/[.06] p-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Available modules</p>
                            <div id="industryModules" class="mt-3 flex flex-wrap gap-2"></div>
                        </div>
                        <div class="industry-preview-extra rounded-lg border border-white/10 bg-white/[.06] p-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Sub-industries</p>
                            <div id="industrySubs" class="mt-3 grid gap-2"></div>
                        </div>
                    </div>

                    <div class="mt-3 rounded-lg bg-white p-4 text-black">
                        <div class="flex items-center justify-between gap-4">
                            <p class="font-black">Dashboard features</p>
                            <span class="rounded-lg bg-[#EAF8F0] px-3 py-1 text-xs font-black text-[#007A3B]">Onboarding ready</span>
                        </div>
                        <div id="industryDashboards" class="mt-3 grid gap-2 sm:grid-cols-2"></div>
                    </div>

                    <div class="industry-preview-extra mt-3 rounded-lg border border-white/10 bg-white/[.06] p-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Provisioned during onboarding</p>
                            <span id="industryPermissionCount" class="rounded-lg bg-[#EAF8F0] px-3 py-1 text-xs font-black text-[#007A3B]"></span>
                        </div>
                        <div id="industryMenus" class="mt-3 flex flex-wrap gap-2"></div>
                    </div>

                    <div class="industry-preview-extra mt-3 grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-white/10 bg-white/[.06] p-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Workflows</p>
                            <div id="industryWorkflows" class="mt-3 grid gap-2"></div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[.06] p-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Reports</p>
                            <div id="industryReports" class="mt-3 grid gap-2"></div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[.06] p-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Templates</p>
                            <div id="industryTemplates" class="mt-3 grid gap-2"></div>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[.06] p-3">
                            <p class="text-xs font-black uppercase text-zinc-400">Default roles</p>
                            <div id="industryRoles" class="mt-3 grid gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="solutions" class="section-pad bg-black px-5 text-white">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-6 lg:grid-cols-[.38fr_1fr]">
                <div>
                    <p class="eyebrow">{{ data_get($benefitsContent, 'eyebrow', 'Platform benefits') }}</p>
                    <h2 class="section-title mt-3 font-black">{{ data_get($benefitsContent, 'title', 'Enterprise foundations for secure growth') }}</h2>
                    <p class="mt-4 leading-7 text-zinc-300">{{ data_get($benefitsContent, 'body', 'Designed for teams that need operational breadth without losing tenant isolation, permissions, reporting, or control.') }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($benefitItems as $benefit)
                        @php
                            $title = is_array($benefit) ? ($benefit['title'] ?? $benefit[0] ?? '') : (string) $benefit;
                            $copy = is_array($benefit) ? ($benefit['copy'] ?? $benefit[1] ?? '') : '';
                        @endphp
                        @continue($title === '')
                        <article class="rounded-lg border border-white/10 bg-white/[.06] p-4">
                            <h3 class="font-black">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-300">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section-pad bg-[#F7F8F5] px-5">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="eyebrow">{{ data_get($stepsContent, 'eyebrow', 'How it works') }}</p>
                <h2 class="section-title mt-3 font-black">{{ data_get($stepsContent, 'title', 'From signup to operations in five steps') }}</h2>
            </div>
            <div class="mt-8 grid gap-3 lg:grid-cols-5">
                @foreach ($stepItems as $step)
                    @php
                        $stepTitle = is_array($step) ? ($step['title'] ?? $step[0] ?? '') : (string) $step;
                        $stepCopy = is_array($step) ? ($step['copy'] ?? $step[1] ?? 'A guided setup keeps the workspace practical from the first login.') : 'A guided setup keeps the workspace practical from the first login.';
                    @endphp
                    @continue($stepTitle === '')
                    <article class="relative rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-[#00A651] text-base font-black text-white">{{ $loop->iteration }}</span>
                        <h3 class="mt-4 text-base font-black">{{ $stepTitle }}</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $stepCopy }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="showcase" class="section-pad px-5">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-6 lg:grid-cols-[.34fr_1fr]">
                <div>
                    <p class="eyebrow">{{ data_get($showcaseContent, 'eyebrow', 'Product showcase') }}</p>
                    <h2 class="section-title mt-3 font-black">{{ data_get($showcaseContent, 'title', 'Switch between real operating views') }}</h2>
                    <div class="mt-6 grid gap-2">
                        @foreach ($showcase as $name => $items)
                            <button type="button" data-product-tab="{{ $name }}" class="product-tab rounded-lg border border-zinc-200 bg-white px-4 py-3 text-left font-black transition hover:border-[#00A651]">{{ $name }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="dark-panel rounded-lg p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-4">
                        <div>
                            <p class="text-xs font-black uppercase text-[#8BE7B6]">{{ data_get($showcaseContent, 'panel_eyebrow', 'Enterprise dashboard') }}</p>
                            <h3 id="productTitle" class="mt-2 text-2xl font-black">{{ array_key_first($showcase) ?: 'Dashboard' }}</h3>
                        </div>
                        <span class="rounded-lg bg-[#00A651] px-4 py-2 text-sm font-black">{{ data_get($showcaseContent, 'panel_badge', 'Live preview') }}</span>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-[.9fr_1.1fr]">
                        <div id="productPanel" class="grid gap-3 sm:grid-cols-2 md:grid-cols-1"></div>
                        <div class="rounded-lg bg-white p-4 text-black">
                            <div class="flex items-center justify-between">
                                <p class="font-black">{{ data_get($showcaseContent, 'trend_label', 'Performance trend') }}</p>
                                <span class="text-sm font-black text-[#00A651]">{{ data_get($showcaseContent, 'trend_value', '+24%') }}</span>
                            </div>
                            <div class="mt-4 flex h-44 items-end gap-2">
                                @foreach ([42, 64, 56, 78, 72, 90, 84] as $height)
                                    <span class="bama-bar flex-1 rounded-t bg-[#00A651]" style="height: {{ $height }}%; --bama-delay: {{ $loop->index * 70 }}ms"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="section-pad bg-[#F7F8F5] px-5">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="eyebrow">{{ data_get($pricingContent, 'eyebrow', 'Pricing') }}</p>
                <h2 class="section-title mt-3 font-black">{{ data_get($pricingContent, 'title', 'Choose the plan that fits your operating stage') }}</h2>
            </div>
            <div class="mt-8 grid gap-4 lg:grid-cols-4">
                @foreach ($plans as $plan)
                    <article class="rounded-[22px] border {{ ! empty($plan['highlight']) ? 'border-[#00A651] bg-black text-white shadow-2xl shadow-[#00A651]/20' : 'border-zinc-200 bg-white shadow-sm' }} p-5">
                        @if (! empty($plan['highlight']))
                            <span class="rounded-lg bg-[#00A651] px-3 py-1 text-xs font-black uppercase">Recommended</span>
                        @endif
                        <h3 class="mt-4 text-2xl font-black">{{ $plan['name'] }}</h3>
                        <p class="mt-2 min-h-14 text-sm leading-6 {{ ! empty($plan['highlight']) ? 'text-zinc-300' : 'text-zinc-600' }}">{{ $plan['tagline'] ?? 'Flexible SaaS plan.' }}</p>
                        <p class="mt-4 text-3xl font-black">{{ ($plan['monthly_price'] ?? 0) > 0 ? number_format($plan['monthly_price']) : 'Custom' }}</p>
                        <p class="mt-1 text-sm {{ ! empty($plan['highlight']) ? 'text-zinc-400' : 'text-zinc-500' }}">{{ ($plan['monthly_price'] ?? 0) > 0 ? $plan['currency'].' monthly' : 'Contact sales' }}</p>
                        <ul class="mt-5 space-y-2 text-sm font-semibold">
                            @foreach (array_slice($plan['features'] ?? [], 0, 5) as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                            <li>User Limits: {{ $plan['limits']['users'] ?? 'Custom' }}</li>
                            <li>Storage: {{ $plan['limits']['storage'] ?? 'Custom' }}</li>
                            <li>Support: {{ $plan['slug'] === 'enterprise' ? 'Dedicated' : 'Standard' }}</li>
                        </ul>
                        <a href="{{ $plan['slug'] === 'enterprise' ? 'mailto:sales@bama.co.ke?subject=Enterprise%20Plan' : route('register.account') }}" class="mt-6 block rounded-lg {{ ! empty($plan['highlight']) ? 'bg-[#00A651] text-white' : 'bg-black text-white' }} px-5 py-3 text-center font-black">{{ $plan['slug'] === 'enterprise' ? data_get($pricingContent, 'enterprise_button', 'Contact Sales') : data_get($pricingContent, 'standard_button', 'Start Free Trial') }}</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-pad px-5">
        <div class="mx-auto max-w-[1280px]">
            @php
                $featuredTestimonial = $testimonialItems[0] ?? [
                    'company' => 'Sarah McNeilly',
                    'quote' => 'As a US company hiring across the continent, we didn’t have any subsidiaries here. I wanted to be able to hire a team compliantly. Workpay was the perfect solution for us...',
                    'metric' => 'ATP Managing Director',
                ];
                $featuredCompany = is_array($featuredTestimonial) ? ($featuredTestimonial['company'] ?? $featuredTestimonial[0] ?? 'Sarah McNeilly') : (string) $featuredTestimonial;
                $featuredQuote = is_array($featuredTestimonial) ? ($featuredTestimonial['quote'] ?? $featuredTestimonial[1] ?? 'As a US company hiring across the continent, we didn’t have any subsidiaries here. I wanted to be able to hire a team compliantly. Workpay was the perfect solution for us...') : '';
                $featuredMetric = is_array($featuredTestimonial) ? ($featuredTestimonial['metric'] ?? $featuredTestimonial[2] ?? 'ATP Managing Director') : 'ATP Managing Director';
            @endphp
            <div class="testimonial-showcase">
                <div class="grid gap-0 lg:grid-cols-[1.06fr_1fr]">
                    <div class="testimonial-quote-panel">
                        <div class="testimonial-quote-mark">“</div>
                        <blockquote class="testimonial-quote">{{ $featuredQuote }}</blockquote>
                        <div class="testimonial-author">
                            <img class="testimonial-avatar" src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=200&q=80" alt="{{ $featuredCompany }}" />
                            <div>
                                <p class="testimonial-author-name">{{ $featuredCompany }}</p>
                                <p class="testimonial-author-role">{{ $featuredMetric }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-media-wrap">
                        <div class="testimonial-accent" aria-hidden="true"></div>
                        <figure class="testimonial-media">
                            <img src="https://images.unsplash.com/photo-1556157382-97eda2d62296?auto=format&fit=crop&w=1200&q=80" alt="{{ $featuredCompany }} speaking during a team meeting" />
                        </figure>
                        <div class="testimonial-badge">
                            <strong>{{ $featuredCompany }}</strong>
                            <span>{{ $featuredMetric }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="section-pad bg-[#F7F8F5] px-5">
        <div class="mx-auto max-w-4xl">
            <div class="text-center">
                <p class="eyebrow">{{ data_get($faqContent, 'eyebrow', 'FAQ') }}</p>
                <h2 class="section-title mt-3 font-black">{{ data_get($faqContent, 'title', 'Common questions') }}</h2>
            </div>
            <div class="mt-8 divide-y divide-zinc-200 rounded-[22px] border border-zinc-200 bg-white shadow-sm">
                @foreach ($faqItems as $faq)
                    @php
                        $question = is_array($faq) ? ($faq['question'] ?? $faq[0] ?? '') : (string) $faq;
                        $answer = is_array($faq) ? ($faq['answer'] ?? $faq[1] ?? '') : '';
                    @endphp
                    @continue($question === '')
                    <details class="group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-black">
                            {{ $question }}
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-[#EAF8F0] text-[#007A3B]">+</span>
                        </summary>
                        <p class="mt-3 leading-7 text-zinc-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 py-14">
        <div class="mx-auto max-w-6xl rounded-[24px] bg-black p-6 text-center text-white sm:p-9">
            <p class="eyebrow">{{ data_get($finalCta, 'eyebrow', 'Final CTA') }}</p>
            <h2 class="section-title mt-3 font-black">{{ data_get($finalCta, 'title', 'Ready to Transform the Way You Run Your Business?') }}</h2>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ data_get($finalCta, 'primary_url', route('register.account')) }}" class="rounded-lg bg-[#00A651] px-6 py-3 text-sm font-black uppercase text-white">{{ data_get($finalCta, 'primary_label', 'Start Free Trial') }}</a>
                <a href="{{ data_get($finalCta, 'secondary_url', 'mailto:sales@bama.co.ke?subject=Schedule%20Demo') }}" class="rounded-lg border border-white/30 px-6 py-3 text-sm font-black uppercase text-white">{{ data_get($finalCta, 'secondary_label', 'Schedule Demo') }}</a>
            </div>
        </div>
    </section>

    @include('landing.partials.page-blocks', ['blocks' => $extraBlocks])

    <footer class="border-t border-zinc-200 bg-[#F1F3EE] px-5 py-10 text-zinc-700">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.2fr_2fr]">
            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="block h-auto w-[104px] max-w-[32vw] object-contain" style="width:104px;height:auto;max-width:32vw;object-fit:contain;">
                </div>
                <p class="mt-4 max-w-sm leading-7">{{ data_get($footerContent, 'body', 'Enterprise SaaS for ERP, CRM, finance, projects, documents, and industry operations.') }}</p>
                <p class="mt-3 text-sm">{{ data_get($footerContent, 'email', 'sales@bama.co.ke') }}<br>{{ data_get($footerContent, 'phone', '+254 700 000 000') }}</p>
            </div>
            <div class="grid gap-8 sm:grid-cols-4">
                @foreach ($footerColumns as $column)
                    @php
                        $heading = is_array($column) ? ($column['heading'] ?? '') : '';
                        $links = is_array($column) ? ($column['links'] ?? []) : [];
                    @endphp
                    @continue(! $heading)
                    <div>
                        <h3 class="font-black text-black">{{ $heading }}</h3>
                        <div class="mt-3 grid gap-2 text-sm">
                            @foreach ($links as $link)
                                @php
                                    $label = is_array($link) ? ($link['label'] ?? '') : $link;
                                    $url = is_array($link) ? ($link['url'] ?? '#top') : '#top';

                                    if (! is_array($link) && $heading === 'Industries') {
                                        $url = route('industries.show', ['industry' => str($label)->lower()->replace(' ', '-')->toString()]);
                                    }
                                @endphp
                                @if($label)
                                    <a href="{{ $url }}" class="hover:text-[#00A651]">{{ $label }}</a>
                                @endif
                            @endforeach
                        </div>
                        @if(strtolower($heading) === 'legal')
                            <div class="mt-5" data-bama-install-card hidden>
                                <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#00A651] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#007A3B] focus:outline-none focus:ring-2 focus:ring-[#00A651] focus:ring-offset-2" data-bama-install hidden>
                                    <i class="bi bi-download"></i>
                                    <span>Download App</span>
                                </button>
                                <p class="mt-2 text-sm text-zinc-600" data-bama-ios-install hidden>On iPhone or iPad, tap Share, then Add to Home Screen.</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </footer>
</main>

<script>
    function animateStatNumber(element) {
        const target = Number(element.dataset.target ?? 0);
        const suffix = element.dataset.suffix ?? '';
        const decimals = Number(element.dataset.decimals ?? 0);

        if (! Number.isFinite(target) || ! element) {
            return;
        }

        const start = target > 0 ? Math.max(target * 0.15, 1) : 0;
        const duration = 1600;
        const startTime = performance.now();

        const update = (now) => {
            const elapsed = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - elapsed, 3);
            const current = start + ((target - start) * eased);
            const formatted = Number(current).toFixed(decimals).replace(/\.0+$|(?<=\.[0-9]*?)0+$/, '');
            element.textContent = `${formatted}${suffix}`;

            if (elapsed < 1) {
                requestAnimationFrame(update);
            } else {
                element.textContent = `${Number(target).toFixed(decimals).replace(/\.0+$|(?<=\.[0-9]*?)0+$/, '')}${suffix}`;
            }
        };

        requestAnimationFrame(update);
    }

    const statNumbers = document.querySelectorAll('.stat-number[data-target]');
    if (statNumbers.length) {
        const statObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (! entry.isIntersecting) {
                    return;
                }

                animateStatNumber(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.45 });

        statNumbers.forEach((node) => {
            statObserver.observe(node);
        });
    }

    const productTabs = @json($showcase);
    const productButtons = document.querySelectorAll('[data-product-tab]');
    const productTitle = document.getElementById('productTitle');
    const productPanel = document.getElementById('productPanel');
    const industryTabs = @json($industryPreview);
    const industryButtons = document.querySelectorAll('[data-industry-tab]');
    const industryTitle = document.getElementById('industryTitle');
    const industrySummary = document.getElementById('industrySummary');
    const industryModules = document.getElementById('industryModules');
    const industrySubs = document.getElementById('industrySubs');
    const industryDashboards = document.getElementById('industryDashboards');
    const industryMenus = document.getElementById('industryMenus');
    const industryReports = document.getElementById('industryReports');
    const industryWorkflows = document.getElementById('industryWorkflows');
    const industryTemplates = document.getElementById('industryTemplates');
    const industryRoles = document.getElementById('industryRoles');
    const industryPermissionCount = document.getElementById('industryPermissionCount');
    const industryPreview = document.querySelector('[data-industry-preview]');
    const industryPreviewToggle = document.querySelector('[data-industry-preview-toggle]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const animatedSelector = [
        '.home-page section > .mx-auto',
        '.home-page article',
        '.home-page .panel',
        '.home-page .soft-panel',
        '.home-page .industry-tab',
        '.home-page [data-product-tab]',
        '.home-page .bama-tile'
    ].join(',');
    const revealObserver = reduceMotion ? null : new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
        });
    }, { threshold: .12, rootMargin: '0px 0px -8% 0px' });

    function registerAnimations(scope = document) {
        const elements = scope.querySelectorAll(animatedSelector);

        elements.forEach((element, index) => {
            if (element.dataset.bamaAnimated) {
                return;
            }

            element.dataset.bamaAnimated = 'true';
            element.classList.add('bama-animate');
            element.style.setProperty('--bama-delay', `${Math.min(index * 35, 280)}ms`);

            if (reduceMotion) {
                element.classList.add('is-visible');
                return;
            }

            revealObserver.observe(element);
        });
    }

    function chip(label) {
        return `<span class="rounded-lg border border-white/10 bg-white/[.08] px-3 py-2 text-xs font-black text-white">${label}</span>`;
    }

    function darkListItem(label) {
        return `<div class="bama-tile rounded-lg border border-white/10 bg-white/[.08] px-3 py-2 text-sm font-bold text-white">${label}</div>`;
    }

    function iconForDashboard(label) {
        const text = String(label).toLowerCase();
        if (/(progress|milestone|status)/.test(text)) return 'bi-kanban';
        if (/(budget|actual|cost control)/.test(text)) return 'bi-calculator';
        if (/(revenue|profit|sales)/.test(text)) return 'bi-graph-up-arrow';
        if (/(cash|payment|receivable)/.test(text)) return 'bi-cash-coin';
        if (/(material|stock|inventory|consumption)/.test(text)) return 'bi-box-seam';
        if (/(tender|conversion|pipeline)/.test(text)) return 'bi-funnel';
        if (/(client|customer|guest|member)/.test(text)) return 'bi-people';
        if (/(booking|reservation|appointment)/.test(text)) return 'bi-calendar-check';
        if (/(compliance|quality|safety)/.test(text)) return 'bi-shield-check';
        if (/(report|analytics|dashboard)/.test(text)) return 'bi-bar-chart';
        return 'bi-speedometer2';
    }

    function lightTile(label) {
        return `<div class="bama-tile flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm font-black"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#00A651] text-white"><i class="bi ${iconForDashboard(label)}"></i></span><span>${label}</span></div>`;
    }

    function setIndustryPreviewExpanded(expanded) {
        if (! industryPreview || ! industryPreviewToggle) {
            return;
        }

        industryPreview.classList.toggle('is-collapsed', ! expanded);
        industryPreviewToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        industryPreviewToggle.setAttribute('aria-label', expanded ? 'Minimize industry preview' : 'Expand industry preview');
    }

    function selectProductTab(name) {
        if (! name || ! productTabs[name]) {
            return;
        }

        productTitle.textContent = name;
        productPanel.innerHTML = productTabs[name].map((item) => `<div class="bama-tile rounded-lg border border-white/10 bg-white/[.07] p-3"><p class="font-black text-white">${item}</p><p class="mt-2 text-xs font-bold uppercase text-zinc-500">Dashboard widget</p></div>`).join('');
        productButtons.forEach((button) => {
            const active = button.dataset.productTab === name;
            button.classList.toggle('border-[#00A651]', active);
            button.classList.toggle('bg-[#EAF8F0]', active);
            button.classList.toggle('text-[#007A3B]', active);
        });
        registerAnimations(productPanel);
    }

    function selectIndustry(slug) {
        const data = industryTabs[slug] || Object.values(industryTabs)[0];
        industryTitle.textContent = data.name;
        industrySummary.textContent = data.description;
        industryModules.innerHTML = data.modules.map(chip).join('');
        industrySubs.innerHTML = data.sub_industries.map(darkListItem).join('');
        industryDashboards.innerHTML = data.dashboard_features.map(lightTile).join('');
        industryMenus.innerHTML = (data.menus || []).map(chip).join('');
        industryReports.innerHTML = (data.reports || []).map(darkListItem).join('');
        industryWorkflows.innerHTML = (data.workflows || []).map(darkListItem).join('');
        industryTemplates.innerHTML = (data.templates || []).map(darkListItem).join('');
        industryRoles.innerHTML = ((data.roles || []).length ? data.roles : [`${data.name} Admin`, `${data.name} Manager`, 'Team Member']).map(darkListItem).join('');
        industryPermissionCount.textContent = `${data.permissions_count || 0} permissions`;
        industryButtons.forEach((button) => {
            const active = button.dataset.industryTab === slug;
            button.classList.toggle('border-[#00A651]', active);
            button.classList.toggle('bg-[#EAF8F0]', active);
        });
        registerAnimations(industryDashboards);
        registerAnimations(industryMenus);
        registerAnimations(industryReports);
        registerAnimations(industryWorkflows);
        registerAnimations(industryTemplates);
        registerAnimations(industryRoles);
    }

    productButtons.forEach((button) => button.addEventListener('click', () => selectProductTab(button.dataset.productTab)));
    industryButtons.forEach((button) => {
        button.addEventListener('mouseenter', () => selectIndustry(button.dataset.industryTab));
        button.addEventListener('focus', () => selectIndustry(button.dataset.industryTab));
    });
    industryPreviewToggle?.addEventListener('click', () => {
        setIndustryPreviewExpanded(industryPreview?.classList.contains('is-collapsed'));
    });
    registerAnimations();
    setIndustryPreviewExpanded(false);
    selectProductTab(Object.keys(productTabs)[0]);
    selectIndustry(Object.keys(industryTabs)[0]);
</script>
@endsection
