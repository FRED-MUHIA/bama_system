@extends('layouts.marketing', [
    'title' => $page->meta_title ?: $page->title,
    'metaDescription' => $page->meta_description,
])

@section('body')
@php
    $marketingSiteContent = \App\Models\MarketingPage::resolve('home')->sections ?: \App\Models\MarketingPage::defaultSections('home');
    $defaults = \App\Models\MarketingPage::defaultSections('home');
    $brand = array_replace_recursive($defaults['brand'], (array) data_get($marketingSiteContent, 'brand', []));
    $headerContent = array_replace_recursive($defaults['header'], (array) data_get($marketingSiteContent, 'header', []));
    $footerContent = array_replace_recursive($defaults['footer'], (array) data_get($marketingSiteContent, 'footer', []));
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
<main class="bama-page min-h-screen">
    <header class="bama-header sticky top-0 z-50">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 text-black no-underline">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="bama-logo">
            </a>
            <div class="hidden items-center gap-5 text-sm font-bold text-black lg:flex">
                <a href="{{ route('landing') }}" class="hover:text-[#00A651]">Home</a>
                @foreach($headerLinks as $link)
                    @if(is_array($link) && ($link['label'] ?? '') && ($link['url'] ?? ''))
                        <a href="{{ $marketingUrl($link['url']) }}" class="hover:text-[#00A651]">{{ $link['label'] }}</a>
                    @endif
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                @if(data_get($headerContent, 'login_label'))
                    <a href="{{ $marketingUrl(data_get($headerContent, 'login_url'), route('login')) }}" class="hidden px-3 py-2 text-sm font-bold text-black hover:text-[#00A651] sm:inline-flex">{{ data_get($headerContent, 'login_label', 'Login') }}</a>
                @endif
                @if(data_get($headerContent, 'cta_label'))
                    <a href="{{ $marketingUrl(data_get($headerContent, 'cta_url'), route('register.account')) }}" class="rounded-lg bg-[#00A651] px-4 py-2 text-sm font-black text-white">{{ data_get($headerContent, 'cta_label', 'Start Free Trial') }}</a>
                @endif
            </div>
        </nav>
    </header>

    @include('landing.partials.page-blocks', ['blocks' => $blocks])

    <footer class="border-t border-zinc-200 bg-[#F1F3EE] px-5 py-10 text-zinc-700">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.2fr_2fr]">
            <div>
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandAlt }}" class="bama-logo">
                <p class="mt-4 max-w-sm leading-7">{{ data_get($footerContent, 'body', 'Enterprise SaaS for ERP, CRM, finance, projects, documents, and industry operations.') }}</p>
                <p class="mt-3 text-sm">{{ data_get($footerContent, 'email', 'sales@bama.co.ke') }}<br>{{ data_get($footerContent, 'phone', '+254 700 000 000') }}</p>
            </div>
            <div class="grid gap-8 sm:grid-cols-4">
                @foreach($footerColumns as $column)
                    @php
                        $heading = is_array($column) ? ($column['heading'] ?? '') : '';
                        $links = is_array($column) ? ($column['links'] ?? []) : [];
                    @endphp
                    @continue(! $heading)
                    <div>
                        <h3 class="font-black text-black">{{ $heading }}</h3>
                        <div class="mt-3 grid gap-2 text-sm">
                            @foreach($links as $link)
                                @php
                                    $label = is_array($link) ? ($link['label'] ?? '') : $link;
                                    $url = is_array($link) ? $marketingUrl($link['url'] ?? route('landing'), route('landing')) : route('landing');
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
@endsection
