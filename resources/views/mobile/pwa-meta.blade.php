@php
    $viteManifest = public_path('build/manifest.json');
    $bamaBuildVersion = is_file($viteManifest) ? hash_file('sha256', $viteManifest) : 'development';
@endphp
<meta name="application-name" content="Bama">
<meta name="mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-title" content="Bama">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Bama">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="format-detection" content="telephone=no">
<meta name="msapplication-TileColor" content="#00A651">
<meta name="msapplication-starturl" content="/app">
<meta name="bama-build-version" content="{{ $bamaBuildVersion }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ asset('pwa-icons/icon-192.png') }}">
<link rel="apple-touch-icon" sizes="512x512" href="{{ asset('pwa-icons/icon-512.png') }}">
