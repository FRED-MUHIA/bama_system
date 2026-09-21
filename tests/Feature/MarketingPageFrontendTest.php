<?php

namespace Tests\Feature;

use App\Models\MarketingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketingPageFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_builder_lists_all_built_in_pages_without_overwriting_content(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $home = MarketingPage::where('slug', 'home')->firstOrFail();
        $home->update(['title' => 'Preserved homepage']);

        $response = $this->actingAs($owner)->get(route('platform.pages.index'))->assertOk();
        foreach (app(\App\Services\IndustrySetupService::class)->implementedSlugs() as $slug) {
            $page = MarketingPage::where('slug', $slug)->firstOrFail();
            $response->assertSee($page->publicUrl(), false);
            $this->get(route('platform.pages.edit', $page))->assertOk()->assertSee('Sections &amp; Buttons', false);
            $this->get($page->publicUrl())->assertOk();
        }
        $this->get(route('platform.pages.index'))->assertOk();
        $this->assertSame('Preserved homepage', $home->fresh()->title);
        $this->assertSame(11, MarketingPage::count());
        $this->get('/?preview=1')->assertOk()->assertSee('Run Your Entire Business');
    }

    public function test_industry_updates_render_publicly_and_drafts_are_hidden(): void
    {
        Storage::fake('public');
        MarketingPage::ensureBuiltInPages();
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $page = MarketingPage::where('slug', 'retail')->firstOrFail();
        $home = MarketingPage::where('slug', 'home')->firstOrFail();
        $sections = $home->sections;
        $sections['brand']['logo_alt'] = 'Shared custom branding';
        $sections['footer']['body'] = 'Shared custom footer';
        $home->update(['sections' => $sections]);
        $this->actingAs($owner)->put(route('platform.pages.update', $page), [
            'title' => 'Custom retail', 'slug' => 'retail', 'meta_title' => 'Custom retail SEO',
            'meta_description' => 'Custom retail description', 'is_published' => 1,
            'sections' => ['hero' => ['title' => 'Custom shop headline', 'body' => 'Custom shop body'], 'copy' => ['cta_title' => 'Custom shop CTA']],
            'industry_modules_json' => json_encode(['Custom shop module']),
            'industry_features_json' => json_encode(['Custom shop feature']),
            'industry_workflows' => "Custom workflow\nSecond workflow",
            'industry_reports' => 'Custom report', 'industry_roles' => 'Custom role', 'industry_menus' => 'Custom menu',
            'industry_sub_industries' => [['name' => 'Custom specialty', 'description' => 'Specialty description']],
            'blocks_json' => json_encode([['type' => 'text', 'title' => 'Extra shop section']]),
            'industry_hero_image' => UploadedFile::fake()->image('retail.png'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        auth()->logout();
        $this->get($page->publicUrl())->assertOk()
            ->assertSee('Custom retail SEO')->assertSee('Custom shop headline')->assertSee('Custom shop body')
            ->assertSee('Custom shop module')->assertSee('Custom shop feature')->assertSee('Custom shop CTA')
            ->assertSee('Custom workflow')->assertSee('Custom report')->assertSee('Custom role')->assertSee('Custom menu')
            ->assertSee('Custom specialty')->assertSee('Extra shop section')->assertSee('Shared custom branding')
            ->assertSee('Shared custom footer')->assertSee('marketing/industry/', false)
            ->assertDontSee('people-industry-mosaic-640.webp', false);
        $this->get('/pages/retail')->assertRedirect($page->publicUrl());
        $page->update(['is_published' => false]);
        $this->get($page->publicUrl())->assertNotFound();
        $this->get('/pages/retail')->assertNotFound();
    }

    public function test_built_in_urls_are_protected_and_printing_page_can_be_saved(): void
    {
        MarketingPage::ensureBuiltInPages();
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $page = MarketingPage::where('slug', 'printing_branding')->firstOrFail();
        $payload = ['title' => $page->title, 'slug' => $page->slug, 'meta_title' => $page->title, 'meta_description' => '', 'is_published' => 1];
        $this->actingAs($owner)->put(route('platform.pages.update', $page), $payload)->assertSessionHasNoErrors();
        $this->put(route('platform.pages.update', $page), array_replace($payload, ['slug' => 'renamed']))->assertSessionHasErrors('slug');
        $this->delete(route('platform.pages.destroy', $page))->assertStatus(422);
    }

    public function test_custom_pages_can_be_created_edited_published_and_linked(): void
    {
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'status' => 'Active']);
        $payload = ['title' => 'About us', 'slug' => 'about-us', 'meta_title' => 'About our team', 'meta_description' => 'Meet our team.', 'blocks_json' => json_encode([['type' => 'text', 'title' => 'Our story']])];
        $this->actingAs($owner)->post(route('platform.pages.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $page = MarketingPage::where('slug', 'about-us')->firstOrFail();
        auth()->logout();
        $this->get($page->publicUrl())->assertNotFound();
        $this->actingAs($owner)->put(route('platform.pages.update', $page), $payload + ['is_published' => 1])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get($page->publicUrl())->assertOk()->assertSee('Our story')->assertSee('About our team');
        $this->get(route('platform.pages.index'))->assertRedirect();
    }

    public function test_public_homepage_renders_without_the_app_login_surface(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Run Your Entire Business From One Unified Platform')
            ->assertSee(route('industries.show', ['industry' => 'construction']), false)
            ->assertSee(route('industries.show', ['industry' => 'chama']), false)
            ->assertDontSee('Bama app login', false)
            ->assertDontSee('href="/login"', false)
            ->assertDontSee('href="/app/login"', false);
    }

    public function test_homepage_updates_do_not_replace_the_app_entry(): void
    {
        Storage::fake('public');

        $page = MarketingPage::where('slug', 'home')->firstOrFail();
        $owner = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($owner)
            ->put(route('platform.pages.update', $page), [
                'title' => 'BAMA Updated Frontend',
                'slug' => 'home',
                'meta_title' => 'BAMA Updated Browser Title',
                'meta_description' => 'Updated public homepage description.',
                'is_published' => 1,
                'sections' => [
                    'brand' => ['logo_alt' => 'Updated BAMA Brand'],
                    'header' => [
                        'login_label' => 'Portal Login',
                        'login_url' => '/login',
                        'demo_label' => 'See Demo',
                        'demo_url' => '/pages/demo',
                        'cta_label' => 'Start Now',
                        'cta_url' => '/register/account',
                    ],
                    'hero' => [
                        'eyebrow' => 'Updated Eyebrow',
                        'title' => 'Updated Homepage Headline',
                        'body' => 'Updated homepage body copy that should render publicly.',
                        'primary_label' => 'Start Now',
                        'primary_url' => '/register/account',
                        'secondary_label' => 'Talk to Sales',
                        'secondary_url' => 'mailto:sales@example.test',
                    ],
                    'insight' => ['title' => 'Updated Insight', 'body' => 'Updated insight copy.'],
                    'trust' => ['heading' => 'Updated Trust Heading'],
                    'final_cta' => ['title' => 'Updated Final CTA', 'primary_label' => 'Go', 'primary_url' => '/register/account'],
                    'footer' => ['body' => 'Updated footer copy.', 'email' => 'hello@example.test', 'phone' => '+254 711 000 000'],
                ],
                'stats_json' => json_encode([['value' => '42', 'label' => 'Updated stat']]),
                'insight_bullets_json' => json_encode([['title' => 'Updated bullet', 'copy' => 'Updated bullet copy']]),
                'logos_json' => json_encode([['label' => 'Updated Logo', 'src' => 'images/trust/apex-build.svg']]),
                'badges_json' => json_encode(['Updated badge']),
                'header_nav_json' => json_encode([['label' => 'Updates', 'url' => '#updates']]),
                'footer_columns_json' => json_encode([['heading' => 'Updated Links', 'links' => [['label' => 'Docs', 'url' => '#docs']]]]),
                'blocks_json' => json_encode([]),
                'brand_favicon' => UploadedFile::fake()->create('favicon.ico', 4, 'image/x-icon'),
            ])
            ->assertRedirect();

        $this->get('/')
            ->assertRedirect(route('platform.dashboard'));
    }

    public function test_industry_pages_expose_editable_content_fields(): void
    {
        $owner = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $page = MarketingPage::create([
            'slug' => 'construction',
            'title' => 'Construction',
            'meta_title' => 'Construction',
            'meta_description' => 'Construction description.',
            'sections' => MarketingPage::defaultSections('construction'),
            'is_published' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('platform.pages.edit', $page))
            ->assertOk()
            ->assertSee('Industry Landing Content');

        $this->actingAs($owner)
            ->put(route('platform.pages.update', $page), [
                'title' => 'Construction',
                'slug' => 'construction',
                'meta_title' => 'Construction',
                'meta_description' => 'Updated construction description.',
                'is_published' => 1,
                'sections' => [
                    'title' => 'Updated Construction',
                    'description' => 'Updated construction copy.',
                    'hero' => ['title' => 'Updated Construction Hero', 'body' => 'Updated hero body'],
                    'media' => ['hero_image_alt' => 'Updated construction alt'],
                ],
                'industry_modules_json' => json_encode(['Project Costing', 'Site Scheduling']),
                'industry_features_json' => json_encode(['Commercial tracking', 'Field visibility']),
                'blocks_json' => json_encode([]),
            ])
            ->assertRedirect();

        $page->refresh();

        $this->assertSame('Updated Construction', $page->sections['title']);
        $this->assertSame('Updated construction copy.', $page->sections['description']);
        $this->assertSame(['Project Costing', 'Site Scheduling'], $page->sections['modules']);
        $this->assertSame(['Commercial tracking', 'Field visibility'], $page->sections['features']);
    }
}
