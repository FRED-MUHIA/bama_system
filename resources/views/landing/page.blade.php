@extends('layouts.marketing', [
    'title' => $page->meta_title ?: $page->title,
    'metaDescription' => $page->meta_description,
])

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
<main class="bama-page min-h-screen">
    @include('landing.partials.site-header')

    @include('landing.partials.page-blocks', ['blocks' => $blocks])

    @include('landing.partials.site-footer')
</main>
@endsection
