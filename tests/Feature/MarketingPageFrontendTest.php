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

    public function test_owner_page_update_is_rendered_on_homepage(): void
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

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('BAMA Updated Browser Title')
            ->assertSee('Updated Homepage Headline')
            ->assertSee('Updated homepage body copy that should render publicly.')
            ->assertSee('See Demo')
            ->assertSee('/pages/demo')
            ->assertSee('Talk to Sales')
            ->assertSee('mailto:sales@example.test')
            ->assertSee('/uploads/marketing/branding/', false);
    }
}
