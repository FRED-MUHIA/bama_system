<?php

namespace App\Http\Controllers;

use App\Models\MarketingPage;
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
        abort_if($page->slug === 'home', 422, 'The homepage cannot be deleted.');

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

        return view('landing.page', [
            'page' => $page,
            'blocks' => $page->sections['blocks'] ?? [],
        ]);
    }

    private function validatedPage(Request $request, ?MarketingPage $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:marketing_pages,slug'.($page ? ','.$page->id : '')],
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
            'brand_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'brand_favicon' => ['nullable', 'file', 'mimes:ico,jpg,jpeg,png,webp,svg', 'max:1024'],
        ]);

        $slug = Str::slug($data['slug']);
        $sections = array_replace_recursive($page?->sections ?? [], (array) $request->input('sections', []));
        $sections['blocks'] = $this->decodeJsonArray($request, 'blocks_json');

        if ($slug === 'home') {
            $sections['stats'] = $this->decodeJsonArray($request, 'stats_json');
            $sections['insight']['bullets'] = $this->decodeJsonArray($request, 'insight_bullets_json');
            $sections['trust']['logos'] = $this->decodeJsonArray($request, 'logos_json');
            $sections['trust']['badges'] = $this->decodeJsonArray($request, 'badges_json');
            $sections['header']['nav_links'] = $this->decodeJsonArray($request, 'header_nav_json');
            $sections['footer']['columns'] = $this->decodeJsonArray($request, 'footer_columns_json');

            if ($request->hasFile('brand_logo')) {
                $this->deletePublicFile(data_get($sections, 'brand.logo_path'));
                $sections['brand']['logo_path'] = $request->file('brand_logo')->store('marketing/branding', 'public');
            }

            if ($request->hasFile('brand_favicon')) {
                $this->deletePublicFile(data_get($sections, 'brand.favicon_path'));
                $sections['brand']['favicon_path'] = $request->file('brand_favicon')->store('marketing/branding', 'public');
            }
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

    private function marketingPagesTableExists(): bool
    {
        return Schema::hasTable('marketing_pages');
    }
}
