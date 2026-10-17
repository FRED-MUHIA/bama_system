<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MarketingPage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketingSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Public marketing needs only these tables, not the operational ERP schema.
        config(['database.default' => 'seo_testing', 'database.connections.seo_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('seo_testing');
        (require database_path('migrations/2026_08_24_000001_create_marketing_pages_table.php'))->up();
        (require database_path('migrations/2026_10_03_000001_create_blog_posts_table.php'))->up();
    }

    public function test_public_pages_have_unique_metadata_and_valid_structured_data(): void
    {
        MarketingPage::ensureBuiltInPages();
        $titles = [];
        $paths = array_merge(['/'], MarketingPage::publicIndustries()->map(fn ($industry) => '/industries/'.$industry['slug'])->all());
        foreach ($paths as $path) {
            $response = $this->get($path.'?utm_source=test')->assertOk();
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//title')->length);
            $titles[] = $xpath->evaluate('string(//title)');
            $this->assertSame(url($path), $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
            $this->assertNotEmpty($xpath->evaluate('string(//meta[@name="description"]/@content)'));
            $this->assertSame(1, $xpath->query('//h1')->length);
            $schema = json_decode($xpath->evaluate('string(//script[@type="application/ld+json"])'), true, 512, JSON_THROW_ON_ERROR);
            $this->assertArrayHasKey('@context', $schema, json_encode($schema));
            $this->assertSame('https://schema.org', $schema['@context']);
            $this->assertSame(url($path), $schema['@graph'][2]['url']);
        }
        $this->assertCount(count($titles), array_unique($titles));
    }

    public function test_sitemap_contains_only_published_canonical_content_and_robots_advertises_it(): void
    {
        MarketingPage::ensureBuiltInPages();
        MarketingPage::where('slug', 'retail')->update(['is_published' => false]);
        MarketingPage::create(['slug' => 'about-us', 'title' => 'About us', 'is_published' => true]);
        MarketingPage::create(['slug' => 'draft-page', 'title' => 'Draft', 'is_published' => false]);
        foreach (['published' => now()->subDay(), 'scheduled' => now()->addDay()] as $slug => $date) {
            BlogPost::create(['slug' => $slug, 'title' => $slug, 'content' => 'Content', 'is_published' => true, 'published_at' => $date]);
        }
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $urls = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
        foreach ([url('/'), url('/pages/about-us'), url('/blog/published')] as $url) {
            $this->assertContains($url, $urls);
        }
        foreach (['/industries/retail', '/pages/draft-page', '/blog/scheduled', '/login', '/pages/home'] as $path) {
            $this->assertNotContains(url($path), $urls);
        }
        $this->assertCount(count($urls), array_unique($urls));
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/industries/retail')->assertNotFound();
        $this->get('/industries/printing-branding')->assertStatus(301)->assertRedirect(route('industries.show', 'printing_branding'));
        $this->get('/pages/salon')->assertStatus(301)->assertRedirect(route('industries.show', 'salon'));
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'), false);
        $this->get('/pages/home')->assertStatus(301)->assertRedirect('/');
    }

    public function test_custom_metadata_is_preserved_and_preview_is_not_indexable(): void
    {
        MarketingPage::ensureBuiltInPages();
        MarketingPage::where('slug', 'home')->update(['meta_title' => 'Custom SEO title', 'meta_description' => 'Custom SEO description']);
        $this->get('/?preview=1')->assertOk()->assertSee('<title>Custom SEO title</title>', false)
            ->assertSee('content="Custom SEO description"', false)->assertSee('content="noindex, follow"', false);
    }
}
