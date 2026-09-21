<?php

namespace App\Http\Controllers;

use App\Models\MarketingPage;
use App\Services\IndustrySetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketingPageController extends Controller
{
    public function index()
    {
        if (! $this->marketingPagesTableExists()) {
            return view('platform.pages.index', [
                'pages' => collect(),
                'migrationMissing' => true,
            ]);
        }

        MarketingPage::ensureBuiltInPages();

        return view('platform.pages.index', [
            'pages' => MarketingPage::query()
                ->orderByRaw("case when slug = 'home' then 0 else 1 end")
                ->orderBy('title')
                ->get(),
            'migrationMissing' => false,
        ]);
    }

    public function create()
    {
        if (! $this->marketingPagesTableExists()) {
            return redirect()
                ->route('platform.pages.index')
                ->with('warning', 'The page builder database table is missing. Run php artisan migrate --force before creating pages.');
        }

        return view('platform.pages.edit', [
            'page' => new MarketingPage([
                'slug' => '',
                'title' => '',
                'sections' => MarketingPage::defaultSections('page'),
                'is_published' => false,
            ]),
            'isCreating' => true,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->marketingPagesTableExists(), 503, 'The page builder database table is missing.');

        $data = $this->validatedPage($request);

        $page = MarketingPage::create($data + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('platform.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(string $page)
    {
        if (! $this->marketingPagesTableExists()) {
            return redirect()
                ->route('platform.pages.index')
                ->with('warning', 'The page builder database table is missing. Run php artisan migrate --force before editing pages.');
        }

        $page = MarketingPage::findOrFail($page);

        return view('platform.pages.edit', [
            'page' => $page,
            'isCreating' => false,
        ]);
    }

    public function update(Request $request, string $page)
    {
        abort_unless($this->marketingPagesTableExists(), 503, 'The page builder database table is missing.');

        $page = MarketingPage::findOrFail($page);
        $data = $this->validatedPage($request, $page);
        $data['updated_by'] = $request->user()->id;

        $page->update($data);

        return back()->with('status', 'Page updated.');
    }

    public function destroy(string $page)
    {
        abort_unless($this->marketingPagesTableExists(), 503, 'The page builder database table is missing.');

        $page = MarketingPage::findOrFail($page);
        abort_if($page->isBuiltIn(), 422, 'Built-in pages cannot be deleted. Unpublish an industry page instead.');

        $page->delete();

        return redirect()->route('platform.pages.index')->with('status', 'Page deleted.');
    }

    public function show(string $slug)
    {
        if (! $this->marketingPagesTableExists()) {
            abort(404);
        }

        $page = MarketingPage::published()->where('slug', $slug)->firstOrFail();

        abort_if($page->slug === 'home', 404);
        if (app(IndustrySetupService::class)->isImplemented($slug)) {
            return redirect($page->publicUrl());
        }
        $homePage = MarketingPage::resolve('home');

        return view('landing.page', [
            'page' => $page,
            'blocks' => $page->sections['blocks'] ?? [],
            'marketingSiteContent' => $homePage->sections ?: MarketingPage::defaultSections('home'),
        ]);
    }

    private function validatedPage(Request $request, ?MarketingPage $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', 'unique:marketing_pages,slug'.($page ? ','.$page->id : '')],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array'],
            'blocks_json' => ['nullable', 'string'],
            'stats_json' => ['nullable', 'string'],
            'insight_bullets_json' => ['nullable', 'string'],
            'logos_json' => ['nullable', 'string'],
            'badges_json' => ['nullable', 'string'],
            'header_nav_json' => ['nullable', 'string'],
            'footer_columns_json' => ['nullable', 'string'],
            'core_modules_json' => ['nullable', 'string'],
            'benefits_json' => ['nullable', 'string'],
            'steps_json' => ['nullable', 'string'],
            'showcase_json' => ['nullable', 'string'],
            'testimonials_json' => ['nullable', 'string'],
            'faqs_json' => ['nullable', 'string'],
            'industry_modules_json' => ['nullable', 'string'],
            'industry_features_json' => ['nullable', 'string'],
            'industry_hero_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'brand_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'brand_favicon' => ['nullable', 'file', 'mimes:ico,jpg,jpeg,png,webp,svg', 'max:1024'],
            'hero_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'insight_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'features_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
            'trust_logo_files' => ['nullable', 'array'],
            'trust_logo_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $slug = app(IndustrySetupService::class)->isImplemented($data['slug'])
            ? app(IndustrySetupService::class)->find($data['slug'])['slug']
            : Str::slug($data['slug']);
        if ($page?->isBuiltIn() && $slug !== $page->slug) {
            throw ValidationException::withMessages(['slug' => 'Built-in page URLs cannot be changed.']);
        }
        if (MarketingPage::where('slug', $slug)->when($page, fn ($query) => $query->whereKeyNot($page->id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'This page URL is already in use.']);
        }
        $sections = array_replace_recursive($page?->sections ?? [], (array) $request->input('sections', []));
        $sections['blocks'] = $this->decodeJsonArray($request, 'blocks_json');

        if (app(IndustrySetupService::class)->isImplemented($slug)) {
            foreach (['workflows', 'reports', 'roles', 'menus'] as $field) {
                if ($request->has('industry_'.$field)) {
                    $request->validate(['industry_'.$field => ['nullable', 'string']]);
                    $sections[$field] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $request->input('industry_'.$field)))));
                }
            }
            if ($request->has('industry_sub_industries')) {
                $request->validate(['industry_sub_industries' => ['nullable', 'array'], 'industry_sub_industries.*.name' => ['nullable', 'string'], 'industry_sub_industries.*.description' => ['nullable', 'string']]);
                $sections['sub_industries'] = array_values(array_filter($request->input('industry_sub_industries', []), fn ($item) => ! empty($item['name'])));
            }
            $sections['title'] = $request->input('sections.title', data_get($sections, 'title', (string) str($slug)->headline()));
            $sections['description'] = $request->input('sections.description', data_get($sections, 'description', 'Use the page builder to update this industry landing page.'));
            $sections['hero'] = array_replace_recursive(data_get($sections, 'hero', []), (array) $request->input('sections.hero', []));
            $sections['modules'] = $this->normalizeIndustryList($request, 'industry_modules_json');
            $sections['features'] = $this->normalizeIndustryList($request, 'industry_features_json');
            $sections['media'] = array_replace_recursive(data_get($sections, 'media', []), (array) $request->input('sections.media', []));

            if ($request->hasFile('industry_hero_image')) {
                $this->deletePublicFile(data_get($sections, 'media.hero_image_path'));
                data_set($sections, 'media.hero_image_path', $request->file('industry_hero_image')->store('marketing/industry', 'public'));
            }
        }

        if ($slug === 'home') {
            $sections['stats'] = $this->decodeJsonArray($request, 'stats_json');
            $sections['insight']['bullets'] = $this->decodeJsonArray($request, 'insight_bullets_json');
            $sections['trust']['logos'] = $this->decodeJsonArray($request, 'logos_json');
            $sections['trust']['badges'] = $this->decodeJsonArray($request, 'badges_json');
            $sections['header']['nav_links'] = $this->decodeJsonArray($request, 'header_nav_json');
            $sections['footer']['columns'] = $this->decodeJsonArray($request, 'footer_columns_json');

            foreach ([
                'core_modules_json' => ['features', 'modules'],
                'benefits_json' => ['benefits', 'items'],
                'steps_json' => ['steps', 'items'],
                'showcase_json' => ['showcase', 'tabs'],
                'testimonials_json' => ['testimonials', 'items'],
                'faqs_json' => ['faq', 'items'],
            ] as $field => $path) {
                if ($request->has($field)) {
                    data_set($sections, implode('.', $path), $this->decodeJsonArray($request, $field));
                }
            }

            if ($request->hasFile('brand_logo')) {
                $this->deletePublicFile(data_get($sections, 'brand.logo_path'));
                $sections['brand']['logo_path'] = $request->file('brand_logo')->store('marketing/branding', 'public');
            }

            if ($request->hasFile('brand_favicon')) {
                $this->deletePublicFile(data_get($sections, 'brand.favicon_path'));
                $sections['brand']['favicon_path'] = $request->file('brand_favicon')->store('marketing/branding', 'public');
            }

            foreach ([
                'hero_image' => 'media.hero_image_path',
                'insight_image' => 'media.insight_image_path',
                'features_image' => 'media.features_image_path',
            ] as $field => $path) {
                if ($request->hasFile($field)) {
                    $this->deletePublicFile(data_get($sections, $path));
                    data_set($sections, $path, $request->file($field)->store('marketing/home', 'public'));
                }
            }

            $sections['trust']['logos'] = $this->applyTrustLogoUploads($request, $sections['trust']['logos']);
        }

        $isPublished = $slug === 'home' || (bool) ($data['is_published'] ?? false);

        return [
            'title' => $data['title'],
            'slug' => $slug,
            'meta_title' => $data['meta_title'] ?: $data['title'],
            'meta_description' => $data['meta_description'],
            'sections' => $sections,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? ($page?->published_at ?? now()) : null,
        ];
    }

    private function decodeJsonArray(Request $request, string $field): array
    {
        $value = trim((string) $request->input($field, '[]'));

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'Enter valid JSON array content.',
            ]);
        }

        return $decoded;
    }

    private function normalizeIndustryList(Request $request, string $field): array
    {
        return collect($this->decodeJsonArray($request, $field))
            ->map(function ($item) {
                if (is_string($item)) {
                    return $item;
                }

                if (is_array($item)) {
                    return $item['name'] ?? $item['title'] ?? $item['label'] ?? null;
                }

                return null;
            })
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->values()
            ->all();
    }

    private function deletePublicFile(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $diskPath = str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : ltrim($path, '/');

        Storage::disk('public')->delete($diskPath);
    }

    private function applyTrustLogoUploads(Request $request, array $logos): array
    {
        $files = $request->file('trust_logo_files', []);

        $files = is_array($files) ? $files : [];

        return collect($logos)
            ->map(function ($logo) use ($files) {
                if (! is_array($logo)) {
                    return $logo;
                }

                $uploadKey = (string) ($logo['upload_key'] ?? '');
                $file = $uploadKey !== '' ? ($files[$uploadKey] ?? null) : null;

                if ($file) {
                    $this->deletePublicFile($logo['src'] ?? null);
                    $logo['src'] = $file->store('marketing/trust', 'public');
                }

                unset($logo['upload_key'], $logo['file_selected']);

                return $logo;
            })
            ->all();
    }

    private function marketingPagesTableExists(): bool
    {
        return Schema::hasTable('marketing_pages');
    }
}
