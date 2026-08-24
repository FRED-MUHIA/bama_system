<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        if (! Schema::hasTable('marketing_pages')) {
            return new static([
                'slug' => $slug,
                'title' => $slug === 'home' ? 'BAMA Business Cloud' : (string) str($slug)->headline(),
                'meta_title' => $slug === 'home' ? 'BAMA Business Cloud' : (string) str($slug)->headline().' | BAMA',
                'meta_description' => 'BAMA business cloud platform.',
                'sections' => static::defaultSections($slug),
                'is_published' => true,
            ]);
        }

        return static::where('slug', $slug)->first() ?? new static([
            'slug' => $slug,
            'title' => $slug === 'home' ? 'BAMA Business Cloud' : (string) str($slug)->headline(),
            'meta_title' => $slug === 'home' ? 'BAMA Business Cloud' : (string) str($slug)->headline().' | BAMA',
            'meta_description' => 'BAMA business cloud platform.',
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
                        'eyebrow' => 'BAMA Page',
                        'title' => (string) str($slug)->headline(),
                        'body' => 'Use the page builder to update this page content.',
                        'button_label' => 'Start Free Trial',
                        'button_url' => '/register/account',
                    ],
                ],
            ];
        }

        return [
            'hero' => [
                'eyebrow' => 'One Platform to Manage Every Business Operation',
                'title' => 'Run Your Entire Business From One Unified Platform',
                'body' => 'Manage customers, projects, finances, inventory, operations, and industry-specific workflows from a single cloud platform.',
                'primary_label' => 'Start Free Trial',
                'primary_url' => '/register/account',
                'secondary_label' => 'Book a Demo',
                'secondary_url' => 'mailto:sales@bama.co.ke?subject=Demo%20Request',
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
                'logos' => ['Apex Build Co.', 'MediCare Group', 'Urban Retail', 'Northline Logistics', 'Prime Properties'],
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
            ],
            'blocks' => [],
        ];
    }
}
