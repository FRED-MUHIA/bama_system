@extends('layouts.marketing', ['title' => 'Verify Email'])

@section('body')
@php
    $legacyLogoPath = 'logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png';
    $brand = (array) data_get(\App\Models\MarketingPage::resolve('home')->sections ?? [], 'brand', []);
    $configuredLogoPath = data_get($brand, 'logo_path');
    $brandLogoUrl = $configuredLogoPath && $configuredLogoPath !== $legacyLogoPath
        ? \App\Support\PublicUpload::url($configuredLogoPath)
        : null;
    $brandName = str_replace(' Admin', '', config('app.name', 'BAMA'));
    $brandAlt = data_get($brand, 'logo_alt', $brandName);
@endphp
<main class="bama-page min-h-screen px-5 py-12">
    <style>
        .verify-brand {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            color: #000000;
            text-decoration: none;
        }

        .verify-brand-mark {
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

        .verify-brand-name {
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1;
        }
    </style>
    <section class="bama-card mx-auto max-w-xl p-8">
        <a href="{{ route('landing') }}" class="verify-brand" aria-label="Back to {{ $brandName }} home">
            @if($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="bama-logo" style="width:170px;height:auto;object-fit:contain;">
            @else
                <span class="verify-brand-mark">{{ strtoupper(substr($brandName, 0, 1)) }}</span>
                <span class="verify-brand-name">{{ $brandName }}</span>
            @endif
        </a>
        <h1 class="mt-10 text-3xl font-black">Verify your email</h1>
        <p class="mt-3 text-black">Use the verification link sent to your inbox to secure your new workspace.</p>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-[#00A651]/40 bg-[#EAF8F0] p-4 text-sm text-[#007A3B]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-500/40 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
            @csrf
            <button class="w-full rounded-lg bg-[#00A651] px-5 py-4 font-bold text-white shadow-xl shadow-[#00A651]/20">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
            @csrf
            <button class="text-sm font-semibold text-black hover:text-[#00A651]">Back to login page</button>
        </form>
    </section>
</main>
@endsection
