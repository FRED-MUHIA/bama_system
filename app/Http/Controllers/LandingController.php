<?php

namespace App\Http\Controllers;

use App\Models\MarketingPage;
use App\Services\IndustrySetupService;
use App\Services\PlanSelectionService;

class LandingController extends Controller
{
    public function __invoke(IndustrySetupService $industries, PlanSelectionService $plans)
    {
        $user = auth()->user();

        if ($user && ! ($user->role === 'super_admin' && request()->boolean('preview'))) {
            return redirect()->route(match ($user->role) {
                'super_admin' => 'platform.dashboard',
                'client_portal' => 'portal.dashboard',
                default => 'dashboard',
            });
        }

        $page = MarketingPage::resolve('home');
        $marketingContent = $page->sections ?: MarketingPage::defaultSections('home');

        return view('landing.index', [
            'industries' => MarketingPage::publicIndustries(),
            'plans' => $plans->all(),
            'marketingPage' => $page,
            'marketingContent' => $marketingContent,
            'marketingSiteContent' => $marketingContent,
        ]);
    }

    public function industry(string $industry, IndustrySetupService $industries, PlanSelectionService $plans)
    {
        abort_unless($industries->isImplemented($industry), 404);

        $definition = $industries->find($industry);
        $page = MarketingPage::resolve($definition['slug']);
        abort_unless($page->is_published, 404);
        $pageSections = array_replace(MarketingPage::defaultSections($definition['slug']), $page->sections ?? []);
        $hero = data_get($pageSections, 'hero', []);
        $media = data_get($pageSections, 'media', []);
        $customModules = data_get($pageSections, 'modules', $definition['modules'] ?? []);
        $customFeatures = data_get($pageSections, 'features', $definition['dashboard']['dashboard_features'] ?? $definition['dashboard']['features'] ?? []);

        return view('landing.industry', [
            'industry' => array_replace($definition, [
                'industry' => data_get($pageSections, 'title', $definition['name']),
                'title' => data_get($pageSections, 'title', $definition['name']),
                'description' => data_get($pageSections, 'description', data_get($hero, 'body', $definition['description'] ?? '')),
                'hero' => array_replace_recursive([
                    'eyebrow' => 'Industry solution',
                    'title' => $definition['name'],
                    'body' => $definition['description'] ?? '',
                ], $hero),
                'media' => array_replace_recursive([
                    'hero_image_path' => 'images/people-industry-mosaic.png',
                    'hero_image_alt' => $definition['name'].' teams using Bama',
                ], $media),
                'modules' => $customModules,
                'features' => $customFeatures,
                'dashboard' => $industries->dashboardFeatures($definition['slug']),
                'hero_image_path' => data_get($pageSections, 'media.hero_image_path', 'images/people-industry-mosaic.png'),
            ], array_intersect_key($pageSections, array_flip(['workflows', 'reports', 'roles', 'menus', 'sub_industries']))),
            'marketingPage' => $page,
            'pageSections' => $pageSections,
            'industries' => MarketingPage::publicIndustries(),
            'plans' => $plans->all(),
            'marketingSiteContent' => MarketingPage::resolve('home')->sections ?: MarketingPage::defaultSections('home'),
        ]);
    }
}
