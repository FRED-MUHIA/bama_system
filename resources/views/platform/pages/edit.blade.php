@extends('layouts.platform')
@section('title', $isCreating ? 'Create Page' : 'Edit Page')
@section('content')
@php
    $sections = old('sections', $page->sections ?: \App\Models\MarketingPage::defaultSections($page->slug ?: 'page'));
    $slug = old('slug', $page->slug);
    $isHome = $slug === 'home';
    $json = fn ($value) => json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
@endphp

<form method="post" action="{{ $isCreating ? route('platform.pages.store') : route('platform.pages.update', $page) }}" data-page-builder-form>
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
                    </div>
                </div>

                <div class="owner-card p-3 mb-4">
                    <h3 class="h5">Homepage Lists</h3>
                    <p class="text-muted small">Use JSON arrays for repeated content.</p>
                    <label class="form-label">Stats</label>
                    <textarea class="form-control font-monospace" name="stats_json" rows="5">{{ $json(data_get($sections, 'stats')) }}</textarea>
                    <label class="form-label mt-3">Insight Bullets</label>
                    <textarea class="form-control font-monospace" name="insight_bullets_json" rows="5">{{ $json(data_get($sections, 'insight.bullets')) }}</textarea>
                    <label class="form-label mt-3">Trust Logos</label>
                    <textarea class="form-control font-monospace" name="logos_json" rows="4">{{ $json(data_get($sections, 'trust.logos')) }}</textarea>
                    <label class="form-label mt-3">Trust Badges</label>
                    <textarea class="form-control font-monospace" name="badges_json" rows="4">{{ $json(data_get($sections, 'trust.badges')) }}</textarea>
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
        let blocks = [];

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

        readInitial();
        blocks.forEach(addBlock);
        document.querySelector('[data-add-block]').addEventListener('click', () => addBlock({ type: 'text' }));
        form.addEventListener('submit', syncHidden);
    })();
</script>
@endsection
