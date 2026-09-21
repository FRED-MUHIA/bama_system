<?php

namespace App\Models;

use App\Services\IndustrySetupService;
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
            $industryService = app(IndustrySetupService::class);

            if ($industryService->isImplemented($slug)) {
                $definition = $industryService->find($slug);
                $name = $definition['name'] ?? (string) str($slug)->headline();
                $description = $definition['description'] ?? 'Use the page builder to update this industry landing page.';
                $modules = $definition['modules'] ?? [];
                $features = $definition['dashboard']['dashboard_features'] ?? $definition['dashboard']['features'] ?? $definition['features'] ?? [];

                return [
                    'eyebrow' => 'Industry solution',
                    'title' => $name,
                    'description' => $description,
                    'hero' => [
                        'eyebrow' => 'Industry solution',
                        'title' => $name,
                        'body' => $description,
                    ],
                    'media' => [
                        'hero_image_path' => 'images/people-industry-mosaic.png',
                        'hero_image_alt' => $name.' teams using Bama',
                    ],
                    'modules' => $modules,
                    'features' => $features,
                    'blocks' => [],
                ];
            }

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
                'login_label' => null,
                'login_url' => null,
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
            'media' => [
                'hero_image_path' => 'images/hero-green-team.png',
                'hero_image_alt' => 'Business leaders using Bama cloud ERP',
                'insight_image_path' => 'images/people-industry-mosaic.png',
                'insight_image_alt' => 'Diverse teams across industries using one business platform',
                'features_image_path' => 'images/people-industry-mosaic.png',
                'features_image_alt' => 'Diverse teams using the platform',
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
            'features' => [
                'eyebrow' => 'Core platform',
                'title' => 'Everything Your Business Needs',
                'body' => 'A complete operating suite for CRM, finance, accounting, projects, inventory, procurement, HR, documents, reporting, and portal workflows.',
                'modules' => [
                    ['name' => 'CRM', 'copy' => 'Manage customers, deals, activities, and sales pipeline.', 'icon' => 'C'],
                    ['name' => 'Finance', 'copy' => 'Invoicing, receipts, expenses, cash flow, and collections.', 'icon' => 'F'],
                    ['name' => 'Accounting', 'copy' => 'Journals, ledgers, periods, reconciliation, and reports.', 'icon' => 'A'],
                    ['name' => 'Projects', 'copy' => 'Tasks, milestones, budgets, delivery progress, and teams.', 'icon' => 'P'],
                    ['name' => 'Inventory', 'copy' => 'Stock levels, products, movements, transfers, and alerts.', 'icon' => 'I'],
                    ['name' => 'Procurement', 'copy' => 'Suppliers, purchase requests, approvals, and orders.', 'icon' => 'Pr'],
                    ['name' => 'HR', 'copy' => 'People, departments, teams, roles, and staff records.', 'icon' => 'H'],
                    ['name' => 'Documents', 'copy' => 'Digital records, approvals, letters, signatures, and files.', 'icon' => 'D'],
                    ['name' => 'Reporting', 'copy' => 'Real-time dashboards, KPIs, analytics, and exports.', 'icon' => 'R'],
                    ['name' => 'Client Portal', 'copy' => 'Secure customer self-service for documents and updates.', 'icon' => 'CP'],
                ],
            ],
            'industries_section' => [
                'eyebrow' => 'Industry solutions',
                'title' => 'Built for the way your industry operates',
                'body' => 'Choose an industry to preview the modules, sub-industries, and dashboard features available during workspace setup.',
            ],
            'benefits' => [
                'eyebrow' => 'Platform benefits',
                'title' => 'Enterprise foundations for secure growth',
                'body' => 'Designed for teams that need operational breadth without losing tenant isolation, permissions, reporting, or control.',
                'items' => [
                    ['title' => 'Multi-Tenant Cloud Platform', 'copy' => 'Every customer organization runs in its own isolated workspace.'],
                    ['title' => 'Role-Based Access Control', 'copy' => 'Give every user the correct module and data access.'],
                    ['title' => 'Advanced Security', 'copy' => 'Audit-ready controls for authentication, permissions, and activity.'],
                    ['title' => 'Scalable Architecture', 'copy' => 'Designed for tenants, businesses, branches, departments, and teams.'],
                    ['title' => 'Custom Branding', 'copy' => 'Tenant logos, colors, favicon, and workspace identity.'],
                    ['title' => 'API Integrations', 'copy' => 'Prepared for mobile apps, vendor portals, and third-party systems.'],
                    ['title' => 'Automation Workflows', 'copy' => 'Approvals, notifications, and repeatable business processes.'],
                    ['title' => 'Real-Time Reporting', 'copy' => 'Live visibility across sales, finance, projects, and operations.'],
                ],
            ],
            'steps' => [
                'eyebrow' => 'How it works',
                'title' => 'From signup to operations in five steps',
                'items' => [
                    ['title' => 'Choose Your Industry', 'copy' => 'A guided setup keeps the workspace practical from the first login.'],
                    ['title' => 'Create Your Workspace', 'copy' => 'A guided setup keeps the workspace practical from the first login.'],
                    ['title' => 'Configure Your Business', 'copy' => 'A guided setup keeps the workspace practical from the first login.'],
                    ['title' => 'Invite Your Team', 'copy' => 'A guided setup keeps the workspace practical from the first login.'],
                    ['title' => 'Start Managing Operations', 'copy' => 'A guided setup keeps the workspace practical from the first login.'],
                ],
            ],
            'showcase' => [
                'eyebrow' => 'Product showcase',
                'title' => 'Switch between real operating views',
                'panel_eyebrow' => 'Enterprise dashboard',
                'panel_badge' => 'Live preview',
                'trend_label' => 'Performance trend',
                'trend_value' => '+24%',
                'tabs' => [
                    ['name' => 'CRM', 'items' => ['Lead pipeline', 'Deal stages', 'Follow-up tasks', 'Customer activity']],
                    ['name' => 'Finance', 'items' => ['Cash position', 'Receivables aging', 'Payment history', 'Expense controls']],
                    ['name' => 'Projects', 'items' => ['Milestones', 'Budget variance', 'Task status', 'Delivery risks']],
                    ['name' => 'Inventory', 'items' => ['Stock alerts', 'Product movement', 'Supplier lead time', 'Branch levels']],
                    ['name' => 'Reports', 'items' => ['Executive KPIs', 'Industry dashboards', 'Trend analysis', 'Export-ready reports']],
                ],
            ],
            'pricing' => [
                'eyebrow' => 'Pricing',
                'title' => 'Choose the plan that fits your operating stage',
                'enterprise_button' => 'Contact Sales',
                'standard_button' => 'Start Free Trial',
            ],
            'testimonials' => [
                'eyebrow' => 'Customer success',
                'title' => 'Teams growing with connected operations',
                'items' => [
                    ['company' => 'Sarah McNeilly', 'quote' => 'As a US company hiring across the continent, we didn’t have any subsidiaries here. I wanted to be able to hire a team compliantly. Workpay was the perfect solution for us...', 'metric' => 'ATP Managing Director'],
                    ['company' => 'Apex Build Co.', 'quote' => 'Construction teams finally see BOQ, procurement, and site progress in the same operating view.', 'metric' => '28% faster project reporting'],
                    ['company' => 'MediCare Group', 'quote' => 'Appointments, pharmacy stock, and finance reports now move through one controlled workspace.', 'metric' => '41% fewer manual reconciliations'],
                ],
            ],
            'faq' => [
                'eyebrow' => 'FAQ',
                'title' => 'Common questions',
                'items' => [
                    ['question' => 'How does multi-tenancy work?', 'answer' => 'Each organization runs in an isolated tenant context with its own users, modules, theme, subscription, and access rules.'],
                    ['question' => 'Can I change plans?', 'answer' => 'Yes. Tenants can move between Starter, Growth, Professional, and Enterprise as their needs grow.'],
                    ['question' => 'Can I customize modules?', 'answer' => 'Yes. Modules can be enabled by industry and extended with tenant-specific workflows and permissions.'],
                    ['question' => 'Do you offer onboarding?', 'answer' => 'Yes. The platform includes a setup checklist and can support assisted onboarding for larger teams.'],
                    ['question' => 'Is my data secure?', 'answer' => 'Security is designed around tenant isolation, role-based access, audit readiness, and controlled authentication.'],
                    ['question' => 'Can I migrate existing data?', 'answer' => 'Yes. Data can be migrated through imports, APIs, or a guided migration plan.'],
                ],
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
