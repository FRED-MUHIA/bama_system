<?php

namespace App\Http\Controllers;

use App\Services\DashboardWidgetRegistry;
use App\Services\AccountEmailReuseService;
use App\Services\IndustrySetupService;
use App\Services\NavigationManager;
use App\Services\UserIndustryPreferenceService;
use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request, NavigationManager $navigation, DashboardWidgetRegistry $widgets, UserIndustryPreferenceService $preferences)
    {
        $industry = $this->currentIndustrySlug($preferences);
        $tenant = ActiveTenant::current();

        return view('profile.edit', [
            'user' => $request->user(),
            'industryWorkspace' => $this->industryWorkspace($request, $navigation, $widgets, $preferences, $industry, $tenant),
        ]);
    }

    public function update(Request $request, NavigationManager $navigation, DashboardWidgetRegistry $widgets, UserIndustryPreferenceService $preferences)
    {
        $user = $request->user();
        $data = $request->validate([
            'phone' => ['nullable', 'max:100'],
            'preferred_language' => ['required', 'in:en,sw'],
            'timezone' => ['required', 'timezone'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['boolean'],
            'industry_workspace' => ['nullable', 'array'],
            'industry_workspace.enabled_menu_keys' => ['nullable', 'array'],
            'industry_workspace.enabled_menu_keys.*' => ['string', 'max:255'],
            'industry_workspace.enabled_widget_slugs' => ['nullable', 'array'],
            'industry_workspace.enabled_widget_slugs.*' => ['string', 'max:255'],
            'industry_workspace.component_density' => ['nullable', 'in:comfortable,compact'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        foreach (['photo', 'signature'] as $field) {
            if (! $request->hasFile($field)) continue;
            $column = $field.'_path';
            if ($user->$column) Storage::disk('public')->delete($user->$column);
            $data[$column] = $request->file($field)->store('users/'.$field, 'public');
        }

        $notificationPreferences = $request->input('notification_preferences', []);
        $data['notification_preferences'] = collect(['email', 'approvals', 'projects', 'security'])
            ->mapWithKeys(fn ($key) => [$key => (bool) ($notificationPreferences[$key] ?? false)])->all();

        $this->saveIndustryWorkspace($request, $navigation, $widgets, $preferences);

        unset($data['photo'], $data['signature'], $data['industry_workspace']);
        $user->update($data);

        return back()->with('status', 'Profile preferences updated. Access assignments were unchanged.');
    }

    public function destroy(Request $request, AccountEmailReuseService $emailReuse)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_delete' => ['accepted'],
        ]);

        $user = $request->user();

        if ($user->role === 'super_admin') {
            throw ValidationException::withMessages([
                'current_password' => 'Owner console accounts cannot be self-deleted from this page.',
            ]);
        }

        DB::transaction(fn () => $emailReuse->holdSelfDeletedAccount($user));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account has been deleted. This email can be used for a new account after four months.');
    }

    private function industryWorkspace(Request $request, NavigationManager $navigation, DashboardWidgetRegistry $widgets, UserIndustryPreferenceService $preferences, ?string $industry, $tenant): array
    {
        if (! $industry) {
            return [];
        }

        $definition = app(IndustrySetupService::class)->find($industry);
        $menuOptions = $navigation->currentIndustryMenuOptions();
        $widgetOptions = $widgets->available($tenant, false)
            ->filter(fn ($widget) => ! $widget->permission || $request->user()->hasPermission($widget->permission))
            ->filter(fn ($widget) => ! $widget->industry || $preferences->normalizeIndustry($widget->industry) === $industry)
            ->values();
        $current = $preferences->preferences($request->user(), $industry);

        return [
            'industry' => $industry,
            'name' => $definition['name'] ?? str($industry)->headline()->toString(),
            'menus' => $menuOptions,
            'widgets' => $widgetOptions,
            'hidden_menu_keys' => $current['hidden_menu_keys'] ?? [],
            'hidden_widget_slugs' => $current['hidden_widget_slugs'] ?? [],
            'component_density' => $current['component_density'] ?? 'comfortable',
        ];
    }

    private function saveIndustryWorkspace(Request $request, NavigationManager $navigation, DashboardWidgetRegistry $widgets, UserIndustryPreferenceService $preferences): void
    {
        $industry = $this->currentIndustrySlug($preferences);

        if (! $industry) {
            return;
        }

        $menuKeys = $navigation->currentIndustryMenuOptions()->pluck('preference_key')->values();
        $widgetSlugs = $widgets->available(ActiveTenant::current(), false)
            ->filter(fn ($widget) => ! $widget->permission || $request->user()->hasPermission($widget->permission))
            ->filter(fn ($widget) => ! $widget->industry || $preferences->normalizeIndustry($widget->industry) === $industry)
            ->pluck('slug')
            ->values();
        $enabledMenus = collect($request->input('industry_workspace.enabled_menu_keys', []))->intersect($menuKeys);
        $enabledWidgets = collect($request->input('industry_workspace.enabled_widget_slugs', []))->intersect($widgetSlugs);

        $preferences->save($request->user(), $industry, [
            'hidden_menu_keys' => $menuKeys->diff($enabledMenus)->values()->all(),
            'hidden_widget_slugs' => $widgetSlugs->diff($enabledWidgets)->values()->all(),
            'component_density' => $request->input('industry_workspace.component_density', 'comfortable'),
        ]);
    }

    private function currentIndustrySlug(UserIndustryPreferenceService $preferences): ?string
    {
        return $preferences->normalizeIndustry(ActiveTenant::current()?->industry ?: ActiveBusiness::current()?->industry);
    }
}
