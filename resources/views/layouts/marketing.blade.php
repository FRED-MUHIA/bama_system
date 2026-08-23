<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'BAMA Business Cloud' }}</title>
    <link rel="icon" href="{{ asset('images/bama-logo-cropped.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --bama-green: #00A651; --bama-black: #000000; --bama-soft: #EAF8F0; }
        [x-cloak] { display: none !important; }
        .bama-noise {
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 44px 44px;
        }
    </style>
</head>
<body class="min-h-screen bg-white font-sans text-zinc-950 antialiased">
    @yield('body')
</body>
</html>
