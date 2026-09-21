@extends('layouts.platform')
@section('title', $isCreating ? 'Create Page' : 'Edit Page')
@section('content')
@php
    $sections = old('sections', $page->sections ?: \App\Models\MarketingPage::defaultSections($page->slug ?: 'page'));
    $slug = old('slug', $page->slug);
    $isHome = $slug === 'home';
    $isIndustryPage = ! $isHome && app(\App\Services\IndustrySetupService::class)->isImplemented((string) $slug);
    $json = fn ($value) => json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $homeDefaults = \App\Models\MarketingPage::defaultSections('home');
    $headerNavLinks = data_get($sections, 'header.nav_links', $homeDefaults['header']['nav_links']);
    $footerColumns = data_get($sections, 'footer.columns', $homeDefaults['footer']['columns']);
    $stats = data_get($sections, 'stats', $homeDefaults['stats']);
    $insightBullets = data_get($sections, 'insight.bullets', $homeDefaults['insight']['bullets']);
    $trustLogos = data_get($sections, 'trust.logos', $homeDefaults['trust']['logos']);
    $trustBadges = data_get($sections, 'trust.badges', $homeDefaults['trust']['badges']);
    $media = array_replace_recursive($homeDefaults['media'], (array) data_get($sections, 'media', []));
    $coreModules = data_get($sections, 'features.modules', $homeDefaults['features']['modules']);
    $benefits = data_get($sections, 'benefits.items', $homeDefaults['benefits']['items']);
    $steps = data_get($sections, 'steps.items', $homeDefaults['steps']['items']);
    $showcaseTabs = data_get($sections, 'showcase.tabs', $homeDefaults['showcase']['tabs']);
    $testimonials = data_get($sections, 'testimonials.items', $homeDefaults['testimonials']['items']);
    $faqs = data_get($sections, 'faq.items', $homeDefaults['faq']['items']);
    $brandLogoUrl = \App\Support\PublicUpload::url(data_get($sections, 'brand.logo_path')) ?: \App\Support\PublicUpload::url('logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png') ?: asset('images/bama-solutions-02.png');
    $faviconUrl = \App\Support\PublicUpload::url(data_get($sections, 'brand.favicon_path')) ?: $brandLogoUrl;
    $heroImageUrl = \App\Support\PublicUpload::url(data_get($media, 'hero_image_path')) ?: asset('images/hero-green-team.png');
    $insightImageUrl = \App\Support\PublicUpload::url(data_get($media, 'insight_image_path')) ?: asset('images/people-industry-mosaic.png');
    $featuresImageUrl = \App\Support\PublicUpload::url(data_get($media, 'features_image_path')) ?: asset('images/people-industry-mosaic.png');
@endphp

<form method="post" action="{{ $isCreating ? route('platform.pages.store') : route('platform.pages.update', $page) }}" enctype="multipart/form-data" data-page-builder-form>
    @csrf
    @unless($isCreating) @method('PUT') @endunless

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="owner-title-eyebrow mb-1">Page Builder</p>
            <h2 class="h4 mb-0">{{ $isCreating ? 'Create website page' : $page->title }}</h2>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="{{ route('platform.pages.index') }}">Back</a>
            <button class="btn btn-owner"><i class="bi bi-save"></i> Save Page</button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="owner-card p-3">
                <h3 class="h5">Page Settings</h3>
                <label class="form-label mt-3">Title</label>
                <input class="form-control" name="title" value="{{ old('title', $page->title) }}" required>

                <label class="form-label mt-3">Slug</label>
                @if(! $isCreating && $page->isBuiltIn())
                    <input type="hidden" name="slug" value="{{ $page->slug }}">
                    <input class="form-control" value="{{ $page->slug }}" disabled>
                    <small class="text-muted">Built-in page URLs are locked.</small>
                @else
                    <input class="form-control" name="slug" value="{{ $slug }}" placeholder="about-us" required>
                    <small class="text-muted">Published URL: /pages/your-slug</small>
                @endif

                <label class="form-label mt-3">SEO Title</label>
                <input class="form-control" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}">

                <label class="form-label mt-3">SEO Description</label>
                <textarea class="form-control" name="meta_description" rows="4">{{ old('meta_description', $page->meta_description) }}</textarea>

                @if($isHome)<input type="hidden" name="is_published" value="1">@endif
                <label class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($isHome || old('is_published', $page->is_published)) @disabled($isHome)>
                    <span class="form-check-label">Published</span>
                </label>
                @if($isHome)<small class="text-muted">The homepage always stays published.</small>@endif

                @if(! $isCreating)
                    <div class="mt-3 rounded border p-2 small">
                        <strong>Public link</strong><br>
                        <a target="_blank" href="{{ $page->publicUrl().($isHome ? '?preview=1' : '') }}">
                            {{ parse_url($page->publicUrl(), PHP_URL_PATH) }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-8">
            @if($isHome)
                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Site Header & Branding</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $brandLogoUrl }}" alt="Current logo" style="width:96px;height:54px;object-fit:contain;border:1px solid #dfe6e2;border-radius:8px;background:#fff;padding:6px">
                                <input class="form-control" type="file" name="brand_logo" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Favicon</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $faviconUrl }}" alt="Current favicon" style="width:42px;height:42px;object-fit:contain;border:1px solid #dfe6e2;border-radius:8px;background:#fff;padding:5px">
                                <input class="form-control" type="file" name="brand_favicon" accept=".ico,.jpg,.jpeg,.png,.webp,.svg,image/*">
                            </div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Logo Alt Text</label><input class="form-control" name="sections[brand][logo_alt]" value="{{ data_get($sections, 'brand.logo_alt', 'Bama Solutions') }}"></div>
                        <div class="col-md-3"><label class="form-label">Demo Label</label><input class="form-control" name="sections[header][demo_label]" value="{{ data_get($sections, 'header.demo_label', 'Book Demo') }}"></div>
                        <div class="col-md-6"><label class="form-label">Demo URL</label><input class="form-control" name="sections[header][demo_url]" value="{{ data_get($sections, 'header.demo_url', 'mailto:sales@bama.co.ke?subject=Demo%20Request') }}"></div>
                        <div class="col-md-3"><label class="form-label">CTA Label</label><input class="form-control" name="sections[header][cta_label]" value="{{ data_get($sections, 'header.cta_label', 'Start Free Trial') }}"></div>
                        <div class="col-md-9"><label class="form-label">CTA URL</label><input class="form-control" name="sections[header][cta_url]" value="{{ data_get($sections, 'header.cta_url', '/register/account') }}"></div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Header Links</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-header-link><i class="bi bi-plus-lg"></i> Add Link</button>
                            </div>
                            <input type="hidden" name="header_nav_json" data-header-links-json value="{{ $json($headerNavLinks) }}">
                            <div class="d-grid gap-2" data-header-links>
                                @foreach($headerNavLinks as $link)
                                    <div class="row g-2 align-items-end" data-header-link>
                                        <div class="col-md-5"><label class="form-label small">Label</label><input class="form-control" data-header-label value="{{ is_array($link) ? ($link['label'] ?? '') : $link }}"></div>
                                        <div class="col-md-6"><label class="form-label small">URL</label><input class="form-control" data-header-url value="{{ is_array($link) ? ($link['url'] ?? '') : '#top' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove header link"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Homepage Hero</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Eyebrow</label><input class="form-control" name="sections[hero][eyebrow]" value="{{ data_get($sections, 'hero.eyebrow') }}"></div>
                        <div class="col-md-6"><label class="form-label">Primary Button</label><input class="form-control" name="sections[hero][primary_label]" value="{{ data_get($sections, 'hero.primary_label') }}"></div>
                        <div class="col-12"><label class="form-label">Headline</label><input class="form-control" name="sections[hero][title]" value="{{ data_get($sections, 'hero.title') }}"></div>
                        <div class="col-12"><label class="form-label">Body</label><textarea class="form-control" name="sections[hero][body]" rows="3">{{ data_get($sections, 'hero.body') }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Primary URL</label><input class="form-control" name="sections[hero][primary_url]" value="{{ data_get($sections, 'hero.primary_url') }}"></div>
                        <div class="col-md-3"><label class="form-label">Secondary Button</label><input class="form-control" name="sections[hero][secondary_label]" value="{{ data_get($sections, 'hero.secondary_label') }}"></div>
                        <div class="col-md-3"><label class="form-label">Secondary URL</label><input class="form-control" name="sections[hero][secondary_url]" value="{{ data_get($sections, 'hero.secondary_url') }}"></div>
                    </div>
                </div>

                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Homepage Images</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Hero Image</label>
                            <img src="{{ $heroImageUrl }}" alt="Current hero image" class="d-block w-100 mb-2" style="height:110px;object-fit:cover;border:1px solid #dfe6e2;border-radius:8px;background:#fff">
                            <input class="form-control" type="file" name="hero_image" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                            <input class="form-control mt-2" name="sections[media][hero_image_alt]" value="{{ data_get($media, 'hero_image_alt') }}" placeholder="Hero image alt text">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Insight Image</label>
                            <img src="{{ $insightImageUrl }}" alt="Current insight image" class="d-block w-100 mb-2" style="height:110px;object-fit:cover;border:1px solid #dfe6e2;border-radius:8px;background:#fff">
                            <input class="form-control" type="file" name="insight_image" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                            <input class="form-control mt-2" name="sections[media][insight_image_alt]" value="{{ data_get($media, 'insight_image_alt') }}" placeholder="Insight image alt text">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Features Image</label>
                            <img src="{{ $featuresImageUrl }}" alt="Current features image" class="d-block w-100 mb-2" style="height:110px;object-fit:cover;border:1px solid #dfe6e2;border-radius:8px;background:#fff">
                            <input class="form-control" type="file" name="features_image" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                            <input class="form-control mt-2" name="sections[media][features_image_alt]" value="{{ data_get($media, 'features_image_alt') }}" placeholder="Features image alt text">
                        </div>
                    </div>
                </div>

                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Homepage Sections</h3>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Insight Eyebrow</label><input class="form-control" name="sections[insight][eyebrow]" value="{{ data_get($sections, 'insight.eyebrow') }}"></div>
                        <div class="col-md-8"><label class="form-label">Insight Title</label><input class="form-control" name="sections[insight][title]" value="{{ data_get($sections, 'insight.title') }}"></div>
                        <div class="col-12"><label class="form-label">Insight Body</label><textarea class="form-control" name="sections[insight][body]" rows="3">{{ data_get($sections, 'insight.body') }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Insight Button Label</label><input class="form-control" name="sections[insight][button_label]" value="{{ data_get($sections, 'insight.button_label') }}"></div>
                        <div class="col-md-6"><label class="form-label">Insight Button URL</label><input class="form-control" name="sections[insight][button_url]" value="{{ data_get($sections, 'insight.button_url') }}"></div>
                        <div class="col-md-6"><label class="form-label">Trust Heading</label><input class="form-control" name="sections[trust][heading]" value="{{ data_get($sections, 'trust.heading') }}"></div>
                        <div class="col-md-6"><label class="form-label">Final CTA Eyebrow</label><input class="form-control" name="sections[final_cta][eyebrow]" value="{{ data_get($sections, 'final_cta.eyebrow') }}"></div>
                        <div class="col-md-4"><label class="form-label">Features Eyebrow</label><input class="form-control" name="sections[features][eyebrow]" value="{{ data_get($sections, 'features.eyebrow', data_get($homeDefaults, 'features.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Features Title</label><input class="form-control" name="sections[features][title]" value="{{ data_get($sections, 'features.title', data_get($homeDefaults, 'features.title')) }}"></div>
                        <div class="col-12"><label class="form-label">Features Body</label><textarea class="form-control" name="sections[features][body]" rows="3">{{ data_get($sections, 'features.body', data_get($homeDefaults, 'features.body')) }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Industries Eyebrow</label><input class="form-control" name="sections[industries_section][eyebrow]" value="{{ data_get($sections, 'industries_section.eyebrow', data_get($homeDefaults, 'industries_section.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Industries Title</label><input class="form-control" name="sections[industries_section][title]" value="{{ data_get($sections, 'industries_section.title', data_get($homeDefaults, 'industries_section.title')) }}"></div>
                        <div class="col-12"><label class="form-label">Industries Body</label><textarea class="form-control" name="sections[industries_section][body]" rows="2">{{ data_get($sections, 'industries_section.body', data_get($homeDefaults, 'industries_section.body')) }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Benefits Eyebrow</label><input class="form-control" name="sections[benefits][eyebrow]" value="{{ data_get($sections, 'benefits.eyebrow', data_get($homeDefaults, 'benefits.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Benefits Title</label><input class="form-control" name="sections[benefits][title]" value="{{ data_get($sections, 'benefits.title', data_get($homeDefaults, 'benefits.title')) }}"></div>
                        <div class="col-12"><label class="form-label">Benefits Body</label><textarea class="form-control" name="sections[benefits][body]" rows="2">{{ data_get($sections, 'benefits.body', data_get($homeDefaults, 'benefits.body')) }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Steps Eyebrow</label><input class="form-control" name="sections[steps][eyebrow]" value="{{ data_get($sections, 'steps.eyebrow', data_get($homeDefaults, 'steps.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Steps Title</label><input class="form-control" name="sections[steps][title]" value="{{ data_get($sections, 'steps.title', data_get($homeDefaults, 'steps.title')) }}"></div>
                        <div class="col-md-4"><label class="form-label">Showcase Eyebrow</label><input class="form-control" name="sections[showcase][eyebrow]" value="{{ data_get($sections, 'showcase.eyebrow', data_get($homeDefaults, 'showcase.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Showcase Title</label><input class="form-control" name="sections[showcase][title]" value="{{ data_get($sections, 'showcase.title', data_get($homeDefaults, 'showcase.title')) }}"></div>
                        <div class="col-md-3"><label class="form-label">Panel Eyebrow</label><input class="form-control" name="sections[showcase][panel_eyebrow]" value="{{ data_get($sections, 'showcase.panel_eyebrow', data_get($homeDefaults, 'showcase.panel_eyebrow')) }}"></div>
                        <div class="col-md-3"><label class="form-label">Panel Badge</label><input class="form-control" name="sections[showcase][panel_badge]" value="{{ data_get($sections, 'showcase.panel_badge', data_get($homeDefaults, 'showcase.panel_badge')) }}"></div>
                        <div class="col-md-3"><label class="form-label">Trend Label</label><input class="form-control" name="sections[showcase][trend_label]" value="{{ data_get($sections, 'showcase.trend_label', data_get($homeDefaults, 'showcase.trend_label')) }}"></div>
                        <div class="col-md-3"><label class="form-label">Trend Value</label><input class="form-control" name="sections[showcase][trend_value]" value="{{ data_get($sections, 'showcase.trend_value', data_get($homeDefaults, 'showcase.trend_value')) }}"></div>
                        <div class="col-md-4"><label class="form-label">Pricing Eyebrow</label><input class="form-control" name="sections[pricing][eyebrow]" value="{{ data_get($sections, 'pricing.eyebrow', data_get($homeDefaults, 'pricing.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Pricing Title</label><input class="form-control" name="sections[pricing][title]" value="{{ data_get($sections, 'pricing.title', data_get($homeDefaults, 'pricing.title')) }}"></div>
                        <div class="col-md-6"><label class="form-label">Pricing Standard Button</label><input class="form-control" name="sections[pricing][standard_button]" value="{{ data_get($sections, 'pricing.standard_button', data_get($homeDefaults, 'pricing.standard_button')) }}"></div>
                        <div class="col-md-6"><label class="form-label">Pricing Enterprise Button</label><input class="form-control" name="sections[pricing][enterprise_button]" value="{{ data_get($sections, 'pricing.enterprise_button', data_get($homeDefaults, 'pricing.enterprise_button')) }}"></div>
                        <div class="col-md-4"><label class="form-label">Testimonials Eyebrow</label><input class="form-control" name="sections[testimonials][eyebrow]" value="{{ data_get($sections, 'testimonials.eyebrow', data_get($homeDefaults, 'testimonials.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">Testimonials Title</label><input class="form-control" name="sections[testimonials][title]" value="{{ data_get($sections, 'testimonials.title', data_get($homeDefaults, 'testimonials.title')) }}"></div>
                        <div class="col-md-4"><label class="form-label">FAQ Eyebrow</label><input class="form-control" name="sections[faq][eyebrow]" value="{{ data_get($sections, 'faq.eyebrow', data_get($homeDefaults, 'faq.eyebrow')) }}"></div>
                        <div class="col-md-8"><label class="form-label">FAQ Title</label><input class="form-control" name="sections[faq][title]" value="{{ data_get($sections, 'faq.title', data_get($homeDefaults, 'faq.title')) }}"></div>
                        <div class="col-12"><label class="form-label">Final CTA Title</label><input class="form-control" name="sections[final_cta][title]" value="{{ data_get($sections, 'final_cta.title') }}"></div>
                        <div class="col-md-3"><label class="form-label">CTA Primary Label</label><input class="form-control" name="sections[final_cta][primary_label]" value="{{ data_get($sections, 'final_cta.primary_label') }}"></div>
                        <div class="col-md-3"><label class="form-label">CTA Primary URL</label><input class="form-control" name="sections[final_cta][primary_url]" value="{{ data_get($sections, 'final_cta.primary_url') }}"></div>
                        <div class="col-md-3"><label class="form-label">CTA Secondary Label</label><input class="form-control" name="sections[final_cta][secondary_label]" value="{{ data_get($sections, 'final_cta.secondary_label') }}"></div>
                        <div class="col-md-3"><label class="form-label">CTA Secondary URL</label><input class="form-control" name="sections[final_cta][secondary_url]" value="{{ data_get($sections, 'final_cta.secondary_url') }}"></div>
                        <div class="col-md-4"><label class="form-label">Footer Body</label><textarea class="form-control" name="sections[footer][body]" rows="3">{{ data_get($sections, 'footer.body') }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Footer Email</label><input class="form-control" name="sections[footer][email]" value="{{ data_get($sections, 'footer.email') }}"></div>
                        <div class="col-md-4"><label class="form-label">Footer Phone</label><input class="form-control" name="sections[footer][phone]" value="{{ data_get($sections, 'footer.phone') }}"></div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Footer Columns</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-footer-column><i class="bi bi-plus-lg"></i> Add Column</button>
                            </div>
                            <input type="hidden" name="footer_columns_json" data-footer-columns-json value="{{ $json($footerColumns) }}">
                            <div class="d-grid gap-3" data-footer-columns>
                                @foreach($footerColumns as $column)
                                    @php
                                        $links = is_array($column) ? ($column['links'] ?? []) : [];
                                    @endphp
                                    <div class="border rounded-3 p-3" data-footer-column>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-11"><label class="form-label small">Column Heading</label><input class="form-control" data-footer-heading value="{{ is_array($column) ? ($column['heading'] ?? '') : '' }}"></div>
                                            <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove footer column"><i class="bi bi-trash"></i></button></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center gap-3 mt-3 mb-2">
                                            <span class="small fw-bold text-muted">Links</span>
                                            <button class="btn btn-sm btn-outline-dark" type="button" data-add-footer-link><i class="bi bi-plus-lg"></i> Add Link</button>
                                        </div>
                                        <div class="d-grid gap-2" data-footer-links>
                                            @foreach($links as $link)
                                                <div class="row g-2 align-items-end" data-footer-link>
                                                    <div class="col-md-5"><label class="form-label small">Label</label><input class="form-control" data-footer-label value="{{ is_array($link) ? ($link['label'] ?? '') : $link }}"></div>
                                                    <div class="col-md-6"><label class="form-label small">URL</label><input class="form-control" data-footer-url value="{{ is_array($link) ? ($link['url'] ?? '#top') : '#top' }}"></div>
                                                    <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove footer link"><i class="bi bi-trash"></i></button></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Homepage Lists</h3>
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Stats</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-stat><i class="bi bi-plus-lg"></i> Add Stat</button>
                            </div>
                            <input type="hidden" name="stats_json" data-stats-json value="{{ $json($stats) }}">
                            <div class="d-grid gap-2" data-stats>
                                @foreach($stats as $stat)
                                    <div class="row g-2 align-items-end" data-stat>
                                        <div class="col-md-5"><label class="form-label small">Value</label><input class="form-control" data-stat-value value="{{ is_array($stat) ? ($stat['value'] ?? '') : $stat }}"></div>
                                        <div class="col-md-6"><label class="form-label small">Label</label><input class="form-control" data-stat-label value="{{ is_array($stat) ? ($stat['label'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove stat"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Insight Bullets</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-insight-bullet><i class="bi bi-plus-lg"></i> Add Bullet</button>
                            </div>
                            <input type="hidden" name="insight_bullets_json" data-insight-bullets-json value="{{ $json($insightBullets) }}">
                            <div class="d-grid gap-2" data-insight-bullets>
                                @foreach($insightBullets as $bullet)
                                    <div class="row g-2 align-items-end" data-insight-bullet>
                                        <div class="col-md-5"><label class="form-label small">Title</label><input class="form-control" data-bullet-title value="{{ is_array($bullet) ? ($bullet['title'] ?? '') : $bullet }}"></div>
                                        <div class="col-md-6"><label class="form-label small">Copy</label><input class="form-control" data-bullet-copy value="{{ is_array($bullet) ? ($bullet['copy'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove bullet"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Trust Logos</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-trust-logo><i class="bi bi-plus-lg"></i> Add Logo</button>
                            </div>
                            <input type="hidden" name="logos_json" data-trust-logos-json value="{{ $json($trustLogos) }}">
                            <div class="d-grid gap-2" data-trust-logos>
                                @foreach($trustLogos as $logoIndex => $logo)
                                    @php
                                        $trustLogoKey = 'logo_'.$logoIndex;
                                    @endphp
                                    <div class="row g-2 align-items-end" data-trust-logo data-upload-key="{{ $trustLogoKey }}">
                                        <div class="col-md-4"><label class="form-label small">Label</label><input class="form-control" data-trust-logo-label value="{{ is_array($logo) ? ($logo['label'] ?? $logo['alt'] ?? '') : $logo }}"></div>
                                        <div class="col-md-4"><label class="form-label small">Image Path or URL</label><input class="form-control" data-trust-logo-src value="{{ is_array($logo) ? ($logo['src'] ?? $logo['image'] ?? '') : '' }}" placeholder="images/trust/logo.svg"></div>
                                        <div class="col-md-3"><label class="form-label small">Upload Logo</label><input class="form-control" type="file" name="trust_logo_files[{{ $trustLogoKey }}]" accept=".jpg,.jpeg,.png,.webp,.svg,image/*"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove logo"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Trust Badges</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-trust-badge><i class="bi bi-plus-lg"></i> Add Badge</button>
                            </div>
                            <input type="hidden" name="badges_json" data-trust-badges-json value="{{ $json($trustBadges) }}">
                            <div class="d-grid gap-2" data-trust-badges>
                                @foreach($trustBadges as $badge)
                                    <div class="input-group" data-trust-badge>
                                        <input class="form-control" data-trust-badge-value value="{{ is_array($badge) ? ($badge['label'] ?? '') : $badge }}">
                                        <button class="btn btn-outline-danger" type="button" data-remove-row aria-label="Remove badge"><i class="bi bi-trash"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Core Modules</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-core-module><i class="bi bi-plus-lg"></i> Add Module</button>
                            </div>
                            <input type="hidden" name="core_modules_json" data-core-modules-json value="{{ $json($coreModules) }}">
                            <div class="d-grid gap-2" data-core-modules>
                                @foreach($coreModules as $module)
                                    <div class="row g-2 align-items-end" data-core-module>
                                        <div class="col-md-2"><label class="form-label small">Icon</label><input class="form-control" data-core-icon value="{{ is_array($module) ? ($module['icon'] ?? '') : '' }}"></div>
                                        <div class="col-md-4"><label class="form-label small">Name</label><input class="form-control" data-core-name value="{{ is_array($module) ? ($module['name'] ?? '') : $module }}"></div>
                                        <div class="col-md-5"><label class="form-label small">Copy</label><input class="form-control" data-core-copy value="{{ is_array($module) ? ($module['copy'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove module"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Benefits</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-benefit><i class="bi bi-plus-lg"></i> Add Benefit</button>
                            </div>
                            <input type="hidden" name="benefits_json" data-benefits-json value="{{ $json($benefits) }}">
                            <div class="d-grid gap-2" data-benefits>
                                @foreach($benefits as $benefit)
                                    <div class="row g-2 align-items-end" data-benefit>
                                        <div class="col-md-5"><label class="form-label small">Title</label><input class="form-control" data-benefit-title value="{{ is_array($benefit) ? ($benefit['title'] ?? '') : $benefit }}"></div>
                                        <div class="col-md-6"><label class="form-label small">Copy</label><input class="form-control" data-benefit-copy value="{{ is_array($benefit) ? ($benefit['copy'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove benefit"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">How It Works Steps</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-step><i class="bi bi-plus-lg"></i> Add Step</button>
                            </div>
                            <input type="hidden" name="steps_json" data-steps-json value="{{ $json($steps) }}">
                            <div class="d-grid gap-2" data-steps>
                                @foreach($steps as $step)
                                    <div class="row g-2 align-items-end" data-step>
                                        <div class="col-md-5"><label class="form-label small">Title</label><input class="form-control" data-step-title value="{{ is_array($step) ? ($step['title'] ?? '') : $step }}"></div>
                                        <div class="col-md-6"><label class="form-label small">Copy</label><input class="form-control" data-step-copy value="{{ is_array($step) ? ($step['copy'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove step"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Product Showcase Tabs</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-showcase><i class="bi bi-plus-lg"></i> Add Tab</button>
                            </div>
                            <input type="hidden" name="showcase_json" data-showcase-json value="{{ $json($showcaseTabs) }}">
                            <div class="d-grid gap-2" data-showcase>
                                @foreach($showcaseTabs as $tab)
                                    <div class="row g-2 align-items-end" data-showcase-tab>
                                        <div class="col-md-4"><label class="form-label small">Tab Name</label><input class="form-control" data-showcase-name value="{{ is_array($tab) ? ($tab['name'] ?? '') : $tab }}"></div>
                                        <div class="col-md-7"><label class="form-label small">Items, one per line</label><textarea class="form-control" rows="2" data-showcase-items>{{ implode("\n", is_array($tab) ? ($tab['items'] ?? []) : []) }}</textarea></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove showcase tab"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">Testimonials</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-testimonial><i class="bi bi-plus-lg"></i> Add Testimonial</button>
                            </div>
                            <input type="hidden" name="testimonials_json" data-testimonials-json value="{{ $json($testimonials) }}">
                            <div class="d-grid gap-2" data-testimonials>
                                @foreach($testimonials as $testimonial)
                                    <div class="row g-2 align-items-end" data-testimonial>
                                        <div class="col-md-3"><label class="form-label small">Metric</label><input class="form-control" data-testimonial-metric value="{{ is_array($testimonial) ? ($testimonial['metric'] ?? '') : '' }}"></div>
                                        <div class="col-md-3"><label class="form-label small">Company</label><input class="form-control" data-testimonial-company value="{{ is_array($testimonial) ? ($testimonial['company'] ?? '') : $testimonial }}"></div>
                                        <div class="col-md-5"><label class="form-label small">Quote</label><input class="form-control" data-testimonial-quote value="{{ is_array($testimonial) ? ($testimonial['quote'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove testimonial"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                <label class="form-label mb-0">FAQ Items</label>
                                <button class="btn btn-sm btn-outline-dark" type="button" data-add-faq><i class="bi bi-plus-lg"></i> Add FAQ</button>
                            </div>
                            <input type="hidden" name="faqs_json" data-faqs-json value="{{ $json($faqs) }}">
                            <div class="d-grid gap-2" data-faqs>
                                @foreach($faqs as $faq)
                                    <div class="row g-2 align-items-end" data-faq>
                                        <div class="col-md-5"><label class="form-label small">Question</label><input class="form-control" data-faq-question value="{{ is_array($faq) ? ($faq['question'] ?? '') : $faq }}"></div>
                                        <div class="col-md-6"><label class="form-label small">Answer</label><input class="form-control" data-faq-answer value="{{ is_array($faq) ? ($faq['answer'] ?? '') : '' }}"></div>
                                        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove FAQ"><i class="bi bi-trash"></i></button></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($isIndustryPage)
                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Industry Landing Content</h3>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Industry Title</label><input class="form-control" name="sections[title]" value="{{ data_get($sections, 'title', data_get($sections, 'hero.title')) }}"></div>
                        <div class="col-md-6"><label class="form-label">Eyebrow</label><input class="form-control" name="sections[hero][eyebrow]" value="{{ data_get($sections, 'hero.eyebrow', 'Industry solution') }}"></div>
                        <div class="col-12"><label class="form-label">Headline</label><input class="form-control" name="sections[hero][title]" value="{{ data_get($sections, 'hero.title', data_get($sections, 'title')) }}"></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="sections[description]" rows="4">{{ data_get($sections, 'description', data_get($sections, 'hero.body')) }}</textarea></div>
                        <div class="col-12"><label class="form-label">Hero Body</label><textarea class="form-control" name="sections[hero][body]" rows="3">{{ data_get($sections, 'hero.body', data_get($sections, 'description')) }}</textarea></div>
                        <div class="col-md-6">
                            <label class="form-label">Hero Image</label>
                            @php $industryHeroImage = \App\Support\PublicUpload::url(data_get($sections, 'media.hero_image_path')) ?: asset('images/people-industry-mosaic.png'); @endphp
                            <img src="{{ $industryHeroImage }}" alt="Industry hero" class="d-block w-100 mb-2" style="height:110px;object-fit:cover;border:1px solid #dfe6e2;border-radius:8px;background:#fff">
                            <input class="form-control" type="file" name="industry_hero_image" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                            <input class="form-control mt-2" name="sections[media][hero_image_alt]" value="{{ data_get($sections, 'media.hero_image_alt', data_get($sections, 'title').' teams using Bama') }}" placeholder="Image alt text">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Workspace Modules</label>
                            <input type="hidden" name="industry_modules_json" data-industry-modules-json value="{{ $json(data_get($sections, 'modules', [])) }}">
                            <textarea class="form-control" rows="8" data-industry-modules-text>{{ implode("\n", array_map(fn($item) => is_array($item) ? ($item['name'] ?? '') : (string) $item, data_get($sections, 'modules', []))) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Key Features</label>
                            <input type="hidden" name="industry_features_json" data-industry-features-json value="{{ $json(data_get($sections, 'features', [])) }}">
                            <textarea class="form-control" rows="8" data-industry-features-text>{{ implode("\n", array_map(fn($item) => is_array($item) ? ($item['name'] ?? $item['title'] ?? '') : (string) $item, data_get($sections, 'features', []))) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Sections &amp; Buttons</h3>
                    <div class="row g-3">
                        @foreach(\App\Models\MarketingPage::industryCopyDefaults($slug) as $key => $default)
                            <div class="col-md-6">
                                <label class="form-label">{{ str($key)->replace('_', ' ')->headline() }}</label>
                                <textarea class="form-control" name="sections[copy][{{ $key }}]" rows="2">{{ data_get($sections, 'copy.'.$key, $default) }}</textarea>
                            </div>
                        @endforeach
                        @foreach(['workflows', 'reports', 'roles', 'menus'] as $field)
                            <div class="col-md-6">
                                <label class="form-label">{{ ucfirst($field) }} (one per line)</label>
                                <textarea class="form-control" name="industry_{{ $field }}" rows="6">{{ old('industry_'.$field, implode("\n", data_get($sections, $field, \App\Models\MarketingPage::defaultSections($slug)[$field] ?? []))) }}</textarea>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <label class="form-label">Sub-industries</label>
                            @foreach(array_merge(data_get($sections, 'sub_industries', \App\Models\MarketingPage::defaultSections($slug)['sub_industries'] ?? []), [['name' => '', 'description' => ''], ['name' => '', 'description' => '']]) as $i => $sub)
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4"><input aria-label="Sub-industry name" class="form-control" name="industry_sub_industries[{{ $i }}][name]" value="{{ old('industry_sub_industries.'.$i.'.name', $sub['name'] ?? '') }}" placeholder="Name"></div>
                                    <div class="col-md-8"><input aria-label="Sub-industry description" class="form-control" name="industry_sub_industries[{{ $i }}][description]" value="{{ old('industry_sub_industries.'.$i.'.description', $sub['description'] ?? '') }}" placeholder="Description"></div>
                                </div>
                            @endforeach
                            <small class="text-muted">Clear a name to remove an entry. Save to add more entries.</small>
                        </div>
                    </div>
                </div>
            @endif

            <div class="owner-card p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h3 class="h5 mb-1">Page Blocks</h3>
                        <p class="text-muted mb-0 small">Add content sections to any page.</p>
                    </div>
                    <button class="btn btn-sm btn-owner" type="button" data-add-block><i class="bi bi-plus-lg"></i> Add Block</button>
                </div>
                <input type="hidden" name="blocks_json" data-blocks-json value="{{ $json(data_get($sections, 'blocks')) }}">
                <div class="d-grid gap-3" data-block-list></div>
            </div>
        </div>
    </div>
</form>

<template data-block-template>
    <div class="border rounded-3 p-3" data-block>
        <div class="d-flex justify-content-between gap-3 mb-2">
            <select class="form-select form-select-sm w-auto" data-block-field="type">
                <option value="hero">Hero</option>
                <option value="text">Text</option>
                <option value="cards">Cards</option>
                <option value="cta">CTA</option>
            </select>
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-block><i class="bi bi-trash"></i></button>
        </div>
        <div class="row g-2">
            <div class="col-md-4"><input class="form-control form-control-sm" data-block-field="eyebrow" placeholder="Eyebrow"></div>
            <div class="col-md-8"><input class="form-control form-control-sm" data-block-field="title" placeholder="Title"></div>
            <div class="col-12"><textarea class="form-control form-control-sm" data-block-field="body" rows="3" placeholder="Body"></textarea></div>
            <div class="col-md-6"><input class="form-control form-control-sm" data-block-field="button_label" placeholder="Button label"></div>
            <div class="col-md-6"><input class="form-control form-control-sm" data-block-field="button_url" placeholder="Button URL"></div>
            <div class="col-12"><textarea class="form-control form-control-sm font-monospace" data-block-field="items_text" rows="3" placeholder="Cards/items, one per line"></textarea></div>
        </div>
    </div>
</template>

<script>
    (() => {
        const form = document.querySelector('[data-page-builder-form]');
        const list = document.querySelector('[data-block-list]');
        const hidden = document.querySelector('[data-blocks-json]');
        const template = document.querySelector('[data-block-template]');
        const headerLinks = document.querySelector('[data-header-links]');
        const headerLinksHidden = document.querySelector('[data-header-links-json]');
        const footerColumns = document.querySelector('[data-footer-columns]');
        const footerColumnsHidden = document.querySelector('[data-footer-columns-json]');
        const stats = document.querySelector('[data-stats]');
        const statsHidden = document.querySelector('[data-stats-json]');
        const insightBullets = document.querySelector('[data-insight-bullets]');
        const insightBulletsHidden = document.querySelector('[data-insight-bullets-json]');
        const trustLogos = document.querySelector('[data-trust-logos]');
        const trustLogosHidden = document.querySelector('[data-trust-logos-json]');
        const trustBadges = document.querySelector('[data-trust-badges]');
        const trustBadgesHidden = document.querySelector('[data-trust-badges-json]');
        const coreModules = document.querySelector('[data-core-modules]');
        const coreModulesHidden = document.querySelector('[data-core-modules-json]');
        const benefits = document.querySelector('[data-benefits]');
        const benefitsHidden = document.querySelector('[data-benefits-json]');
        const steps = document.querySelector('[data-steps]');
        const stepsHidden = document.querySelector('[data-steps-json]');
        const showcase = document.querySelector('[data-showcase]');
        const showcaseHidden = document.querySelector('[data-showcase-json]');
        const testimonials = document.querySelector('[data-testimonials]');
        const testimonialsHidden = document.querySelector('[data-testimonials-json]');
        const faqs = document.querySelector('[data-faqs]');
        const faqsHidden = document.querySelector('[data-faqs-json]');
        const industryModulesText = document.querySelector('[data-industry-modules-text]');
        const industryModulesHidden = document.querySelector('[data-industry-modules-json]');
        const industryFeaturesText = document.querySelector('[data-industry-features-text]');
        const industryFeaturesHidden = document.querySelector('[data-industry-features-json]');
        let blocks = [];
        let dynamicKey = Date.now();

        const escapeAttribute = (value = '') => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const readInitial = () => {
            try { blocks = JSON.parse(hidden.value || '[]') || []; } catch (error) { blocks = []; }
        };

        const syncIndustryTextFields = () => {
            if (industryModulesText && industryModulesHidden) {
                const lines = industryModulesText.value.split('\n').map((line) => line.trim()).filter(Boolean);
                industryModulesHidden.value = JSON.stringify(lines.map((line) => ({ name: line })));
            }

            if (industryFeaturesText && industryFeaturesHidden) {
                const lines = industryFeaturesText.value.split('\n').map((line) => line.trim()).filter(Boolean);
                industryFeaturesHidden.value = JSON.stringify(lines.map((line) => ({ name: line })));
            }
        };

        const syncHidden = () => {
            blocks = [...list.querySelectorAll('[data-block]')].map((node) => {
                const block = {};
                node.querySelectorAll('[data-block-field]').forEach((field) => {
                    const key = field.dataset.blockField;
                    if (key === 'items_text') {
                        block.items = field.value.split('\n').map((line) => line.trim()).filter(Boolean);
                    } else {
                        block[key] = field.value.trim();
                    }
                });
                return block;
            });
            hidden.value = JSON.stringify(blocks);
        };

        const addBlock = (block = {}) => {
            const node = template.content.firstElementChild.cloneNode(true);
            node.querySelectorAll('[data-block-field]').forEach((field) => {
                const key = field.dataset.blockField;
                field.value = key === 'items_text' ? (block.items || []).map((item) => typeof item === 'string' ? item : (item.title || '')).join('\n') : (block[key] || '');
                field.addEventListener('input', syncHidden);
                field.addEventListener('change', syncHidden);
            });
            node.querySelector('[data-remove-block]').addEventListener('click', () => {
                node.remove();
                syncHidden();
            });
            list.appendChild(node);
            syncHidden();
        };

        const headerLinkRow = (link = {}) => `
            <div class="row g-2 align-items-end" data-header-link>
                <div class="col-md-5"><label class="form-label small">Label</label><input class="form-control" data-header-label value="${escapeAttribute(link.label || '')}"></div>
                <div class="col-md-6"><label class="form-label small">URL</label><input class="form-control" data-header-url value="${escapeAttribute(link.url || '')}"></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove header link"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const footerLinkRow = (link = {}) => `
            <div class="row g-2 align-items-end" data-footer-link>
                <div class="col-md-5"><label class="form-label small">Label</label><input class="form-control" data-footer-label value="${escapeAttribute(link.label || '')}"></div>
                <div class="col-md-6"><label class="form-label small">URL</label><input class="form-control" data-footer-url value="${escapeAttribute(link.url || '')}"></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove footer link"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const footerColumnRow = () => `
            <div class="border rounded-3 p-3" data-footer-column>
                <div class="row g-2 align-items-end">
                    <div class="col-md-11"><label class="form-label small">Column Heading</label><input class="form-control" data-footer-heading></div>
                    <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove footer column"><i class="bi bi-trash"></i></button></div>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-3 mt-3 mb-2">
                    <span class="small fw-bold text-muted">Links</span>
                    <button class="btn btn-sm btn-outline-dark" type="button" data-add-footer-link><i class="bi bi-plus-lg"></i> Add Link</button>
                </div>
                <div class="d-grid gap-2" data-footer-links>${footerLinkRow()}</div>
            </div>`;

        const statRow = () => `
            <div class="row g-2 align-items-end" data-stat>
                <div class="col-md-5"><label class="form-label small">Value</label><input class="form-control" data-stat-value></div>
                <div class="col-md-6"><label class="form-label small">Label</label><input class="form-control" data-stat-label></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove stat"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const insightBulletRow = () => `
            <div class="row g-2 align-items-end" data-insight-bullet>
                <div class="col-md-5"><label class="form-label small">Title</label><input class="form-control" data-bullet-title></div>
                <div class="col-md-6"><label class="form-label small">Copy</label><input class="form-control" data-bullet-copy></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove bullet"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const nextUploadKey = () => `logo_new_${dynamicKey++}`;

        const trustLogoRow = () => {
            const uploadKey = nextUploadKey();
            return `
            <div class="row g-2 align-items-end" data-trust-logo data-upload-key="${uploadKey}">
                <div class="col-md-4"><label class="form-label small">Label</label><input class="form-control" data-trust-logo-label></div>
                <div class="col-md-4"><label class="form-label small">Image Path or URL</label><input class="form-control" data-trust-logo-src placeholder="images/trust/logo.svg"></div>
                <div class="col-md-3"><label class="form-label small">Upload Logo</label><input class="form-control" type="file" name="trust_logo_files[${uploadKey}]" accept=".jpg,.jpeg,.png,.webp,.svg,image/*"></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove logo"><i class="bi bi-trash"></i></button></div>
            </div>`;
        };

        const coreModuleRow = () => `
            <div class="row g-2 align-items-end" data-core-module>
                <div class="col-md-2"><label class="form-label small">Icon</label><input class="form-control" data-core-icon></div>
                <div class="col-md-4"><label class="form-label small">Name</label><input class="form-control" data-core-name></div>
                <div class="col-md-5"><label class="form-label small">Copy</label><input class="form-control" data-core-copy></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove module"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const twoFieldRow = (kind, titleLabel = 'Title', copyLabel = 'Copy') => `
            <div class="row g-2 align-items-end" data-${kind}>
                <div class="col-md-5"><label class="form-label small">${titleLabel}</label><input class="form-control" data-${kind}-title></div>
                <div class="col-md-6"><label class="form-label small">${copyLabel}</label><input class="form-control" data-${kind}-copy></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove ${kind}"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const showcaseRow = () => `
            <div class="row g-2 align-items-end" data-showcase-tab>
                <div class="col-md-4"><label class="form-label small">Tab Name</label><input class="form-control" data-showcase-name></div>
                <div class="col-md-7"><label class="form-label small">Items, one per line</label><textarea class="form-control" rows="2" data-showcase-items></textarea></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove showcase tab"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const testimonialRow = () => `
            <div class="row g-2 align-items-end" data-testimonial>
                <div class="col-md-3"><label class="form-label small">Metric</label><input class="form-control" data-testimonial-metric></div>
                <div class="col-md-3"><label class="form-label small">Company</label><input class="form-control" data-testimonial-company></div>
                <div class="col-md-5"><label class="form-label small">Quote</label><input class="form-control" data-testimonial-quote></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove testimonial"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const faqRow = () => `
            <div class="row g-2 align-items-end" data-faq>
                <div class="col-md-5"><label class="form-label small">Question</label><input class="form-control" data-faq-question></div>
                <div class="col-md-6"><label class="form-label small">Answer</label><input class="form-control" data-faq-answer></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove FAQ"><i class="bi bi-trash"></i></button></div>
            </div>`;

        const smallListRow = (kind) => `
            <div class="input-group" data-trust-${kind}>
                <input class="form-control" data-trust-${kind}-value>
                <button class="btn btn-outline-danger" type="button" data-remove-row aria-label="Remove ${kind}"><i class="bi bi-trash"></i></button>
            </div>`;

        const syncHeaderLinks = () => {
            if (! headerLinks || ! headerLinksHidden) return;

            headerLinksHidden.value = JSON.stringify([...headerLinks.querySelectorAll('[data-header-link]')]
                .map((row) => ({
                    label: row.querySelector('[data-header-label]').value.trim(),
                    url: row.querySelector('[data-header-url]').value.trim(),
                }))
                .filter((link) => link.label || link.url));
        };

        const syncFooterColumns = () => {
            if (! footerColumns || ! footerColumnsHidden) return;

            footerColumnsHidden.value = JSON.stringify([...footerColumns.querySelectorAll('[data-footer-column]')]
                .map((column) => ({
                    heading: column.querySelector('[data-footer-heading]').value.trim(),
                    links: [...column.querySelectorAll('[data-footer-link]')]
                        .map((row) => ({
                            label: row.querySelector('[data-footer-label]').value.trim(),
                            url: row.querySelector('[data-footer-url]').value.trim(),
                        }))
                        .filter((link) => link.label || link.url),
                }))
                .filter((column) => column.heading || column.links.length));
        };

        const syncHomeLists = () => {
            if (stats && statsHidden) {
                statsHidden.value = JSON.stringify([...stats.querySelectorAll('[data-stat]')]
                    .map((row) => ({
                        value: row.querySelector('[data-stat-value]').value.trim(),
                        label: row.querySelector('[data-stat-label]').value.trim(),
                    }))
                    .filter((item) => item.value || item.label));
            }

            if (insightBullets && insightBulletsHidden) {
                insightBulletsHidden.value = JSON.stringify([...insightBullets.querySelectorAll('[data-insight-bullet]')]
                    .map((row) => ({
                        title: row.querySelector('[data-bullet-title]').value.trim(),
                        copy: row.querySelector('[data-bullet-copy]').value.trim(),
                    }))
                    .filter((item) => item.title || item.copy));
            }

            if (trustLogos && trustLogosHidden) {
                trustLogosHidden.value = JSON.stringify([...trustLogos.querySelectorAll('[data-trust-logo]')]
                    .map((row) => {
                        const fileInput = row.querySelector('input[type="file"]');

                        return {
                            label: row.querySelector('[data-trust-logo-label]').value.trim(),
                            src: row.querySelector('[data-trust-logo-src]').value.trim(),
                            upload_key: row.dataset.uploadKey || '',
                            file_selected: Boolean(fileInput?.files?.length),
                        };
                    })
                    .filter((logo) => logo.label || logo.src || logo.file_selected));
            }

            if (trustBadges && trustBadgesHidden) {
                trustBadgesHidden.value = JSON.stringify([...trustBadges.querySelectorAll('[data-trust-badge-value]')]
                    .map((input) => input.value.trim())
                    .filter(Boolean));
            }

            if (coreModules && coreModulesHidden) {
                coreModulesHidden.value = JSON.stringify([...coreModules.querySelectorAll('[data-core-module]')]
                    .map((row) => ({
                        icon: row.querySelector('[data-core-icon]').value.trim(),
                        name: row.querySelector('[data-core-name]').value.trim(),
                        copy: row.querySelector('[data-core-copy]').value.trim(),
                    }))
                    .filter((item) => item.icon || item.name || item.copy));
            }

            if (benefits && benefitsHidden) {
                benefitsHidden.value = JSON.stringify([...benefits.querySelectorAll('[data-benefit]')]
                    .map((row) => ({
                        title: row.querySelector('[data-benefit-title]').value.trim(),
                        copy: row.querySelector('[data-benefit-copy]').value.trim(),
                    }))
                    .filter((item) => item.title || item.copy));
            }

            if (steps && stepsHidden) {
                stepsHidden.value = JSON.stringify([...steps.querySelectorAll('[data-step]')]
                    .map((row) => ({
                        title: row.querySelector('[data-step-title]').value.trim(),
                        copy: row.querySelector('[data-step-copy]').value.trim(),
                    }))
                    .filter((item) => item.title || item.copy));
            }

            if (showcase && showcaseHidden) {
                showcaseHidden.value = JSON.stringify([...showcase.querySelectorAll('[data-showcase-tab]')]
                    .map((row) => ({
                        name: row.querySelector('[data-showcase-name]').value.trim(),
                        items: row.querySelector('[data-showcase-items]').value.split('\n').map((item) => item.trim()).filter(Boolean),
                    }))
                    .filter((item) => item.name || item.items.length));
            }

            if (testimonials && testimonialsHidden) {
                testimonialsHidden.value = JSON.stringify([...testimonials.querySelectorAll('[data-testimonial]')]
                    .map((row) => ({
                        metric: row.querySelector('[data-testimonial-metric]').value.trim(),
                        company: row.querySelector('[data-testimonial-company]').value.trim(),
                        quote: row.querySelector('[data-testimonial-quote]').value.trim(),
                    }))
                    .filter((item) => item.metric || item.company || item.quote));
            }

            if (faqs && faqsHidden) {
                faqsHidden.value = JSON.stringify([...faqs.querySelectorAll('[data-faq]')]
                    .map((row) => ({
                        question: row.querySelector('[data-faq-question]').value.trim(),
                        answer: row.querySelector('[data-faq-answer]').value.trim(),
                    }))
                    .filter((item) => item.question || item.answer));
            }
        };

        const syncAll = () => {
            syncIndustryTextFields();
            syncHidden();
            syncHeaderLinks();
            syncFooterColumns();
            syncHomeLists();
        };

        readInitial();
        blocks.forEach(addBlock);
        document.querySelector('[data-add-block]').addEventListener('click', () => addBlock({ type: 'text' }));

        form.addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (! button) return;

            if (button.matches('[data-add-header-link]')) {
                headerLinks?.insertAdjacentHTML('beforeend', headerLinkRow({ label: 'New Link', url: '#top' }));
                syncAll();
            } else if (button.matches('[data-add-footer-column]')) {
                footerColumns?.insertAdjacentHTML('beforeend', footerColumnRow());
                syncAll();
            } else if (button.matches('[data-add-footer-link]')) {
                button.closest('[data-footer-column]')?.querySelector('[data-footer-links]')?.insertAdjacentHTML('beforeend', footerLinkRow());
                syncAll();
            } else if (button.matches('[data-add-stat]')) {
                stats?.insertAdjacentHTML('beforeend', statRow());
                syncAll();
            } else if (button.matches('[data-add-insight-bullet]')) {
                insightBullets?.insertAdjacentHTML('beforeend', insightBulletRow());
                syncAll();
            } else if (button.matches('[data-add-trust-logo]')) {
                trustLogos?.insertAdjacentHTML('beforeend', trustLogoRow());
                syncAll();
            } else if (button.matches('[data-add-trust-badge]')) {
                trustBadges?.insertAdjacentHTML('beforeend', smallListRow('badge'));
                syncAll();
            } else if (button.matches('[data-add-core-module]')) {
                coreModules?.insertAdjacentHTML('beforeend', coreModuleRow());
                syncAll();
            } else if (button.matches('[data-add-benefit]')) {
                benefits?.insertAdjacentHTML('beforeend', twoFieldRow('benefit'));
                syncAll();
            } else if (button.matches('[data-add-step]')) {
                steps?.insertAdjacentHTML('beforeend', twoFieldRow('step'));
                syncAll();
            } else if (button.matches('[data-add-showcase]')) {
                showcase?.insertAdjacentHTML('beforeend', showcaseRow());
                syncAll();
            } else if (button.matches('[data-add-testimonial]')) {
                testimonials?.insertAdjacentHTML('beforeend', testimonialRow());
                syncAll();
            } else if (button.matches('[data-add-faq]')) {
                faqs?.insertAdjacentHTML('beforeend', faqRow());
                syncAll();
            } else if (button.matches('[data-remove-row]')) {
                button.closest('[data-header-link], [data-footer-column], [data-footer-link], [data-stat], [data-insight-bullet], [data-trust-logo], [data-trust-badge], [data-core-module], [data-benefit], [data-step], [data-showcase-tab], [data-testimonial], [data-faq]')?.remove();
                syncAll();
            }
        });

        form.addEventListener('input', syncAll);
        form.addEventListener('change', syncAll);
        form.addEventListener('submit', syncAll);
        syncAll();
    })();
</script>
@endsection
