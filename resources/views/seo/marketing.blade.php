@php
    $seoPage = $marketingPage ?? $page;
    $isHome = $seoPage->slug === 'home';
    $isIndustry = request()->routeIs('industries.show');
    $seoDefaults = \App\Models\MarketingPage::defaultSections($seoPage->slug);
    $seoName = $isIndustry ? $industry['industry'] : $seoPage->title;
    $defaultTitle = $isHome ? 'Business Management Software in Kenya | Bama'
        : ($isIndustry ? $seoName.' Management Software | Bama' : $seoName.' | Bama');
    $genericTitles = [$seoPage->title, $seoDefaults['title'] ?? '', 'Bama Business Cloud', (string) str($seoPage->slug)->headline().' | Bama'];
    $title = filled($seoPage->meta_title) && ! in_array($seoPage->meta_title, $genericTitles, true)
        ? $seoPage->meta_title : $defaultTitle;
    $defaultDescription = $isHome
        ? 'Manage accounting, invoicing, inventory, payroll and industry operations with Bama business management software for businesses in Kenya.'
        : ($seoPage->sections['description'] ?? data_get($seoPage->sections, 'hero.body') ?? $seoDefaults['description'] ?? $seoName);
    $metaDescription = \Illuminate\Support\Str::limit(trim(strip_tags(
        filled($seoPage->meta_description) && $seoPage->meta_description !== 'Bama business cloud platform.'
            ? $seoPage->meta_description : $defaultDescription
    )), 160, '…');
    $canonical = $seoPage->publicUrl();
    $seoImage = \App\Support\PublicUpload::url(data_get($seoPage->sections, 'media.hero_image_path')) ?: asset('images/people-industry-mosaic.png');
    $homeUrl = route('landing');
    $graph = [
        ['@type' => 'Organization', '@id' => $homeUrl.'#organization', 'name' => 'Bama', 'url' => $homeUrl],
        ['@type' => 'WebSite', '@id' => $homeUrl.'#website', 'name' => 'Bama Business Cloud', 'url' => $homeUrl, 'publisher' => ['@id' => $homeUrl.'#organization']],
        ['@type' => 'WebPage', '@id' => $canonical.'#webpage', 'url' => $canonical, 'name' => $title,
            'description' => $metaDescription, 'isPartOf' => ['@id' => $homeUrl.'#website'], 'inLanguage' => str_replace('_', '-', app()->getLocale())],
    ];
    if (! $isHome) {
        $graph[2]['breadcrumb'] = ['@id' => $canonical.'#breadcrumb'];
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canonical.'#breadcrumb', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $homeUrl],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $seoName, 'item' => $canonical],
        ]];
    }
    $structuredData = ['@context' => 'https://schema.org', '@graph' => $graph];
@endphp
<title>{{ $title }}</title>
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ request()->has('preview') ? 'noindex, follow' : 'index, follow, max-image-preview:large' }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Bama Business Cloud">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
