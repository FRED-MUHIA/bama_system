<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'BAMA Business Cloud' }}</title>
    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endisset
    @php
        $marketingSiteContent = $marketingSiteContent ?? \App\Models\MarketingPage::resolve('home')->sections ?? \App\Models\MarketingPage::defaultSections('home');
        $marketingBrand = array_replace_recursive(\App\Models\MarketingPage::defaultSections('home')['brand'], (array) data_get($marketingSiteContent, 'brand', []));
        $marketingFaviconPath = 'images/bama-favicon.png';
        $marketingFaviconUrl = asset($marketingFaviconPath);
        $marketingFaviconVersion = file_exists(public_path($marketingFaviconPath)) ? filemtime(public_path($marketingFaviconPath)) : 'bama';
        $marketingFaviconHref = $marketingFaviconUrl.(str_contains($marketingFaviconUrl, '?') ? '&' : '?').'v='.rawurlencode((string) $marketingFaviconVersion);
    @endphp
    <link rel="icon" href="{{ $marketingFaviconHref }}">
    <link rel="shortcut icon" href="{{ $marketingFaviconHref }}">
    <link rel="apple-touch-icon" href="{{ $marketingFaviconHref }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:opsz,wght@14..32,100..900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter+Tight:opsz,wght@14..32,100..900&display=swap" rel="stylesheet"></noscript>
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
            --bama-font-body: 'tt_normsregular', 'TT Norms', 'Inter Tight', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            --bama-font-heading: 'McQueen', 'tt_normsregular', 'TT Norms', 'Inter Tight', 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        body {
            background: var(--bama-page);
            color: var(--bama-black);
            font-family: var(--bama-font-body) !important;
        }

        body,
        button,
        input,
        select,
        textarea {
            letter-spacing: 0;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--bama-font-heading) !important;
            font-weight: 600 !important;
            letter-spacing: 0;
            text-rendering: geometricPrecision;
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
    @yield('body')
</body>
</html>
