<?php

namespace App\Models;

use App\Support\SchemaCache;
use Illuminate\Database\Eloquent\Model;

class MarketingPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'sections',
        'is_published',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sections' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public static function resolve(string $slug): self
    {
        if (! SchemaCache::hasTable('marketing_pages')) {
            return new static([
                'slug' => $slug,
                'title' => $slug === 'home' ? 'Bama Business Cloud' : (string) str($slug)->headline(),
                'meta_title' => $slug === 'home' ? 'Bama Business Cloud' : (string) str($slug)->headline().' | Bama',
                'meta_description' => 'Bama business cloud platform.',
                'sections' => static::defaultSections($slug),
                'is_published' => true,
            ]);
        }

        return static::where('slug', $slug)->first() ?? new static([
            'slug' => $slug,
            'title' => $slug === 'home' ? 'Bama Business Cloud' : (string) str($slug)->headline(),
            'meta_title' => $slug === 'home' ? 'Bama Business Cloud' : (string) str($slug)->headline().' | Bama',
            'meta_description' => 'Bama business cloud platform.',
            'sections' => static::defaultSections($slug),
            'is_published' => true,
        ]);
    }

    public static function defaultSections(string $slug = 'home'): array
    {
        if ($slug !== 'home') {
            return [
                'blocks' => [
                    [
                        'type' => 'hero',
                        'eyebrow' => 'Bama Page',
                        'title' => (string) str($slug)->headline(),
                        'body' => 'Use the page builder to update this page content.',
                        'button_label' => 'Start Free Trial',
                        'button_url' => '/register/account',
                    ],
                ],
            ];
        }

        return [
            'brand' => [
                'logo_path' => 'logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png',
                'favicon_path' => null,
                'logo_alt' => 'Bama Solutions',
            ],
            'header' => [
                'nav_links' => [
                    ['label' => 'Features', 'url' => '#features'],
                    ['label' => 'Industries', 'url' => '#industries'],
                    ['label' => 'Solutions', 'url' => '#solutions'],
                    ['label' => 'Pricing', 'url' => '#pricing'],
                    ['label' => 'Resources', 'url' => '#faq'],
                ],
                'login_label' => 'Login',
                'login_url' => '/login',
                'demo_label' => null,
                'demo_url' => null,
                'cta_label' => 'Start Free Trial',
                'cta_url' => '/register/account',
            ],
            'hero' => [
                'eyebrow' => 'One Platform to Manage Every Business Operation',
                'title' => 'Run Your Entire Business From One Unified Platform',
                'body' => 'Manage customers, projects, finances, inventory, operations, and industry-specific workflows from a single cloud platform.',
                'primary_label' => 'Start Free Trial',
                'primary_url' => '/register/account',
                'secondary_label' => null,
                'secondary_url' => null,
            ],
            'stats' => [
                ['value' => '99.9%', 'label' => 'Uptime'],
                ['value' => '1000s', 'label' => 'Businesses'],
                ['value' => 'Millions', 'label' => 'Transactions'],
                ['value' => 'Secure', 'label' => 'Security'],
            ],
            'insight' => [
                'eyebrow' => 'Operational intelligence',
                'title' => 'See every business signal clearly',
                'body' => 'Finance, CRM, projects, procurement, inventory, and industry dashboards come together in one connected operating view.',
                'button_label' => 'Explore dashboards',
                'button_url' => '#solutions',
                'bullets' => [
                    ['title' => 'Live KPIs', 'copy' => 'Real-time decisions'],
                    ['title' => 'Unified data', 'copy' => 'One operating view'],
                    ['title' => 'Executive clarity', 'copy' => 'Faster reporting'],
                ],
            ],
            'trust' => [
                'heading' => 'Trusted by organizations across multiple industries',
                'logos' => [
                    ['label' => 'Apex Build Co.', 'src' => 'images/trust/apex-build.svg'],
                    ['label' => 'MediCare Group', 'src' => 'images/trust/medicare-group.svg'],
                    ['label' => 'Urban Retail', 'src' => 'images/trust/urban-retail.svg'],
                    ['label' => 'Northline Logistics', 'src' => 'images/trust/northline-logistics.svg'],
                    ['label' => 'Prime Properties', 'src' => 'images/trust/prime-properties.svg'],
                ],
                'badges' => ['Enterprise Security', 'Tenant Isolation', 'Role-Based Access', 'Audit Ready'],
            ],
            'final_cta' => [
                'eyebrow' => 'Final CTA',
                'title' => 'Ready to Transform the Way You Run Your Business?',
                'primary_label' => 'Start Free Trial',
                'primary_url' => '/register/account',
                'secondary_label' => 'Schedule Demo',
                'secondary_url' => 'mailto:sales@bama.co.ke?subject=Schedule%20Demo',
            ],
            'footer' => [
                'body' => 'Enterprise SaaS for ERP, CRM, finance, projects, documents, and industry operations.',
                'email' => 'sales@bama.co.ke',
                'phone' => '+254 700 000 000',
                'columns' => [
                    ['heading' => 'Products', 'links' => ['CRM', 'Finance', 'Projects', 'Inventory']],
                    ['heading' => 'Industries', 'links' => ['Construction', 'Real Estate', 'Retail', 'Hospitality']],
                    ['heading' => 'Company', 'links' => ['Pricing', 'Documentation', 'Support', 'Social Media']],
                    ['heading' => 'Legal', 'links' => ['Privacy Policy', 'Terms']],
                ],
            ],
            'blocks' => [],
        ];
    }
}
