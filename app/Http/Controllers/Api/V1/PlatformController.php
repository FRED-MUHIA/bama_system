<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardWidgetRegistry;
use App\Services\ModuleRegistry;
use App\Services\NavigationManager;
use App\Services\SubscriptionManager;
use App\Services\UserIndustryPreferenceService;
use App\Support\ActiveTenant;

class PlatformController extends Controller
{
    public function context(ModuleRegistry $modules, NavigationManager $navigation, DashboardWidgetRegistry $widgets, SubscriptionManager $subscriptions, UserIndustryPreferenceService $preferences)
    {
        $tenant = ActiveTenant::current();
        $industry = $preferences->normalizeIndustry($tenant?->industry);
        $profilePreferences = auth()->check() ? $preferences->preferences(auth()->user(), $industry) : [];

        return response()->json([
            'tenant' => $tenant?->only(['id', 'name', 'slug', 'industry', 'status']),
            'subscription_active' => $subscriptions->active($tenant),
            'modules' => $modules->enabled($tenant)->map->only(['slug', 'name', 'type', 'industry', 'icon', 'route'])->values(),
            'navigation' => $navigation->sidebar()->values(),
            'widgets' => $widgets->forUser(auth()->id(), $tenant)->map->only(['slug', 'name', 'module_slug', 'industry', 'component'])->values(),
            'profile_preferences' => [
                'industry' => $industry,
                'component_density' => $profilePreferences['component_density'] ?? 'comfortable',
                'hidden_menu_keys' => $profilePreferences['hidden_menu_keys'] ?? [],
                'hidden_widget_slugs' => $profilePreferences['hidden_widget_slugs'] ?? [],
            ],
        ]);
    }
}
