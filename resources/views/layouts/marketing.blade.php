<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bama Business Cloud' }}</title>
    @stack('seo')
    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endisset
    @php
        $marketingDefaults = \App\Models\MarketingPage::defaultSections('home');
        $marketingSiteContent = $marketingSiteContent ?? ($marketingContent ?? null);

        if (! $marketingSiteContent && isset($marketingPage)) {
            $marketingSiteContent = $marketingPage->sections;
        }

        if (! $marketingSiteContent) {
            $marketingHomePage = \App\Models\MarketingPage::resolve('home');
            $marketingSiteContent = $marketingHomePage->sections ?: $marketingDefaults;
        }

        $marketingBrand = array_replace_recursive($marketingDefaults['brand'], (array) data_get($marketingSiteContent, 'brand', []));
        $marketingFaviconPath = data_get($marketingBrand, 'favicon_path') ?: 'images/bama-favicon.png';
        $marketingFaviconHref = \App\Support\PublicUpload::url($marketingFaviconPath) ?: asset('images/bama-favicon.png');
        $marketingFaviconFile = \App\Support\PublicUpload::filePath($marketingFaviconPath);
        $marketingFaviconVersion = $marketingFaviconFile && file_exists($marketingFaviconFile) ? filemtime($marketingFaviconFile) : 'bama';
        $marketingFaviconHref = $marketingFaviconHref.(str_contains($marketingFaviconHref, '?') ? '&' : '?').'v='.rawurlencode((string) $marketingFaviconVersion);
    @endphp
    <link rel="icon" href="{{ $marketingFaviconHref }}">
    <link rel="shortcut icon" href="{{ $marketingFaviconHref }}">
    <link rel="apple-touch-icon" href="{{ $marketingFaviconHref }}">
    <meta name="theme-color" content="#050806">
    @include('mobile.pwa-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"></noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @font-face {
            font-family: 'tt_normsregular';
            src: local('tt_normsregular'), local('TT Norms Regular'), local('TT Norms');
            font-weight: 400 900;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --bama-green: #00A651;
            --bama-green-dark: #007A3B;
            --bama-black: #000000;
            --bama-soft: #EAF8F0;
            --bama-page: #F7F8F5;
            --bama-line: #e5e7eb;
            --bama-font-body: 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --bama-font-heading: 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --bama-font-display: 'Manrope', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Uploaded media stays within its reserved space on every public page. */
        .bama-page:not(.home-page) img { max-width: 100%; }
        .bama-page .bama-media-frame {
            position: relative;
            width: 100%;
            min-width: 0;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            border-radius: 12px;
            background: #071B12;
        }
        .bama-page .bama-media-frame > picture {
            position: absolute;
            inset: 0;
            display: block;
        }
        .bama-page .bama-media-frame img,
        .bama-page img.bama-upload-image {
            display: block;
            width: 100%;
            height: 100%;
            max-width: 100%;
            object-fit: contain;
            object-position: center;
        }

        .industry-showcase {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 24px;
            max-width: 1440px;
            margin: 0 auto;
            padding: 36px 24px 32px;
            background: #fff;
        }
        .industry-showcase-track { min-width: 0; }
        .industry-showcase-slide {
            display: none;
            grid-template-columns: minmax(0, .82fr) minmax(0, 1.18fr);
            align-items: center;
            gap: clamp(24px, 4vw, 72px);
            min-height: 460px;
        }
        .industry-showcase-slide.is-active { display: grid; animation: industry-slide-in .35s ease both; }
        .industry-showcase-copy { max-width: 570px; padding: 18px 0; }
        .industry-showcase-visual { min-width: 0; }
        .industry-showcase-image-frame {
            width: 100%;
            height: clamp(320px, 42vw, 600px);
            overflow: hidden;
            border-radius: 18px;
            background: #f7f8f5;
        }
        .industry-showcase-image-frame picture { display: block; width: 100%; height: 100%; }
        .industry-showcase-image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        .industry-showcase-controls {
            position: absolute;
            z-index: 2;
            left: 24px;
            bottom: 42px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .industry-showcase-controls > button {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 1px solid #dfe5e1;
            border-radius: 50%;
            background: #fff;
            color: #071b12;
        }
        .industry-showcase-controls > button:hover { border-color: #00a651; color: #007a3b; }
        .industry-showcase-dots { display: flex; align-items: center; gap: 7px; }
        .industry-showcase-dots button { width: 8px; height: 8px; padding: 0; border: 0; border-radius: 99px; background: #cbd5ce; }
        .industry-showcase-dots button[aria-current="true"] { width: 24px; background: #00a651; }
        .industry-showcase-includes { border-top: 1px solid #e8ece9; padding: 20px 0 0; }
        .industry-showcase-module-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 13px; }
        .industry-showcase-module { display: flex; min-width: 0; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid #e7ece8; border-radius: 10px; background: #fbfcfb; color: #17221b; font-weight: 700; }
        .industry-showcase-module i { flex: 0 0 auto; color: #00a651; }
        @keyframes industry-slide-in { from { opacity: .35; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
        @media (min-width: 1024px) {
            .industry-showcase { grid-template-columns: minmax(0, 1fr) 310px; gap: 32px; padding: 54px 36px; }
            .industry-showcase-includes { align-self: center; border: 1px solid #e8ece9; border-radius: 16px; padding: 22px; box-shadow: 0 12px 36px rgba(15,23,42,.06); }
            .industry-showcase-module-list { grid-template-columns: 1fr; max-height: 500px; overflow-y: auto; }
            .industry-showcase-controls { left: 36px; bottom: 60px; }
        }
        @media (max-width: 1023px) {
            .industry-showcase-slide { grid-template-columns: 1fr; gap: 20px; min-height: 0; }
            .industry-showcase-copy { max-width: none; }
            .industry-showcase-image-frame { height: clamp(240px, 56vw, 440px); }
            .industry-showcase-controls { position: static; justify-content: center; padding: 2px 0 8px; }
        }
        @media (max-width: 640px) {
            .industry-showcase { padding: 24px 18px; }
            .industry-showcase-module-list { grid-template-columns: 1fr; }
        }

        body {
            background: var(--bama-page);
            color: var(--bama-black);
            font-family: var(--bama-font-body) !important;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            font-optical-sizing: auto;
            font-synthesis-weight: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body,
        button,
        input,
        select,
        textarea,
        table {
            font-family: var(--bama-font-body) !important;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            letter-spacing: 0;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--bama-font-heading) !important;
            font-weight: 700 !important;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            letter-spacing: -0.02em;
            line-height: 1.08;
            text-rendering: geometricPrecision;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        [x-cloak] { display: none !important; }
        .bama-noise {
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .bama-page {
            background: var(--bama-page);
            color: var(--bama-black);
            font-family: var(--bama-font-body) !important;
        }

        .bama-header {
            border-bottom: 1px solid var(--bama-line);
            background: rgba(251, 252, 250, .96);
            backdrop-filter: blur(18px);
        }

        .bama-logo {
            display: block;
            width: 92px;
            max-width: 24vw;
            height: auto;
            object-fit: contain;
        }

        .bama-eyebrow {
            color: var(--bama-green);
            font-size: .75rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .bama-card {
            border: 1px solid var(--bama-line);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        }

        .bama-chip {
            border-radius: 8px;
            background: var(--bama-soft);
            color: var(--bama-green-dark);
            font-weight: 800;
        }
    </style>
</head>
<body class="min-h-screen bg-[#F7F8F5] font-sans text-black antialiased">
    @include('mobile.pwa-shell')
    @yield('body')
</body>
<script>
document.querySelectorAll('[data-industry-slider]').forEach((slider) => {
    const slides = Array.from(slider.querySelectorAll('[data-industry-slide]'));
    const dots = Array.from(slider.querySelectorAll('[data-industry-go]'));
    if (slides.length < 2) return;

    let active = 0;
    const show = (index) => {
        active = (index + slides.length) % slides.length;
        slides.forEach((slide, position) => {
            const selected = position === active;
            slide.classList.toggle('is-active', selected);
            slide.setAttribute('aria-hidden', selected ? 'false' : 'true');
        });
        dots.forEach((dot, position) => dot.setAttribute('aria-current', position === active ? 'true' : 'false'));
    };

    slider.querySelector('[data-industry-prev]')?.addEventListener('click', () => show(active - 1));
    slider.querySelector('[data-industry-next]')?.addEventListener('click', () => show(active + 1));
    dots.forEach((dot) => dot.addEventListener('click', () => show(Number(dot.dataset.industryGo))));
});
</script>
</html>
