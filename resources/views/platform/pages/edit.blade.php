@extends('layouts.platform')
@section('title', $isCreating ? 'Create Page' : 'Edit Page')
@section('content')
@php
    $sections = old('sections', $page->sections ?: \App\Models\MarketingPage::defaultSections($page->slug ?: 'page'));
    $slug = old('slug', $page->slug);
    $isHome = $slug === 'home';
    $json = fn ($value) => json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $homeDefaults = \App\Models\MarketingPage::defaultSections('home');
    $headerNavLinks = data_get($sections, 'header.nav_links', $homeDefaults['header']['nav_links']);
    $footerColumns = data_get($sections, 'footer.columns', $homeDefaults['footer']['columns']);
    $stats = data_get($sections, 'stats', $homeDefaults['stats']);
    $insightBullets = data_get($sections, 'insight.bullets', $homeDefaults['insight']['bullets']);
    $trustLogos = data_get($sections, 'trust.logos', $homeDefaults['trust']['logos']);
    $trustBadges = data_get($sections, 'trust.badges', $homeDefaults['trust']['badges']);
    $brandLogoUrl = \App\Support\PublicUpload::url(data_get($sections, 'brand.logo_path')) ?: asset('images/bama-solutions-02.png');
    $faviconUrl = \App\Support\PublicUpload::url(data_get($sections, 'brand.favicon_path')) ?: asset('images/bama-solutions-02.png');
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
                @if($isHome)
                    <input type="hidden" name="slug" value="home">
                    <input class="form-control" value="home" disabled>
                    <small class="text-muted">The homepage slug is locked.</small>
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
                        <a target="_blank" href="{{ $isHome ? route('landing') : route('marketing.pages.show', $page->slug) }}">
                            {{ $isHome ? '/' : '/pages/'.$page->slug }}
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
                        <div class="col-md-6"><label class="form-label">Login URL</label><input class="form-control" name="sections[header][login_url]" value="{{ data_get($sections, 'header.login_url', '/login') }}"></div>
                        <div class="col-md-3"><label class="form-label">Login Label</label><input class="form-control" name="sections[header][login_label]" value="{{ data_get($sections, 'header.login_label', 'Login') }}"></div>
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
                    <h3 class="h5">Homepage Sections</h3>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Insight Eyebrow</label><input class="form-control" name="sections[insight][eyebrow]" value="{{ data_get($sections, 'insight.eyebrow') }}"></div>
                        <div class="col-md-8"><label class="form-label">Insight Title</label><input class="form-control" name="sections[insight][title]" value="{{ data_get($sections, 'insight.title') }}"></div>
                        <div class="col-12"><label class="form-label">Insight Body</label><textarea class="form-control" name="sections[insight][body]" rows="3">{{ data_get($sections, 'insight.body') }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Insight Button Label</label><input class="form-control" name="sections[insight][button_label]" value="{{ data_get($sections, 'insight.button_label') }}"></div>
                        <div class="col-md-6"><label class="form-label">Insight Button URL</label><input class="form-control" name="sections[insight][button_url]" value="{{ data_get($sections, 'insight.button_url') }}"></div>
                        <div class="col-md-6"><label class="form-label">Trust Heading</label><input class="form-control" name="sections[trust][heading]" value="{{ data_get($sections, 'trust.heading') }}"></div>
                        <div class="col-md-6"><label class="form-label">Final CTA Eyebrow</label><input class="form-control" name="sections[final_cta][eyebrow]" value="{{ data_get($sections, 'final_cta.eyebrow') }}"></div>
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
                                @foreach($trustLogos as $logo)
                                    <div class="row g-2 align-items-end" data-trust-logo>
                                        <div class="col-md-4"><label class="form-label small">Label</label><input class="form-control" data-trust-logo-label value="{{ is_array($logo) ? ($logo['label'] ?? $logo['alt'] ?? '') : $logo }}"></div>
                                        <div class="col-md-7"><label class="form-label small">Image Path or URL</label><input class="form-control" data-trust-logo-src value="{{ is_array($logo) ? ($logo['src'] ?? $logo['image'] ?? '') : '' }}" placeholder="images/trust/logo.svg"></div>
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
                    </div>
                </div>
            @endif

            <div class="owner-card p-3">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h3 class="h5 mb-1">Page Blocks</h3>
                        <p class="text-muted mb-0 small">Use blocks for custom pages or extra homepage sections.</p>
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
        let blocks = [];

        const escapeAttribute = (value = '') => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const readInitial = () => {
            try { blocks = JSON.parse(hidden.value || '[]') || []; } catch (error) { blocks = []; }
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

        const trustLogoRow = () => `
            <div class="row g-2 align-items-end" data-trust-logo>
                <div class="col-md-4"><label class="form-label small">Label</label><input class="form-control" data-trust-logo-label></div>
                <div class="col-md-7"><label class="form-label small">Image Path or URL</label><input class="form-control" data-trust-logo-src placeholder="images/trust/logo.svg"></div>
                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-row aria-label="Remove logo"><i class="bi bi-trash"></i></button></div>
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
                    .map((row) => ({
                        label: row.querySelector('[data-trust-logo-label]').value.trim(),
                        src: row.querySelector('[data-trust-logo-src]').value.trim(),
                    }))
                    .filter((logo) => logo.label || logo.src));
            }

            if (trustBadges && trustBadgesHidden) {
                trustBadgesHidden.value = JSON.stringify([...trustBadges.querySelectorAll('[data-trust-badge-value]')]
                    .map((input) => input.value.trim())
                    .filter(Boolean));
            }
        };

        const syncAll = () => {
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
            } else if (button.matches('[data-remove-row]')) {
                button.closest('[data-header-link], [data-footer-column], [data-footer-link], [data-stat], [data-insight-bullet], [data-trust-logo], [data-trust-badge]')?.remove();
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
