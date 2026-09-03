<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UserIndustryPreferenceService
{
    public function normalizeIndustry(?string $industry): ?string
    {
        if (! $industry) {
            return null;
        }

        $slug = Str::of($industry)->snake(' ')->slug('-')->toString();

        return [
            'professionalservices' => 'professional-services',
            'salon-spa' => 'salon',
            'salon-and-spa' => 'salon',
            'fitness-gym' => 'fitness',
            'realestate' => 'real-estate',
            'accountingfirm' => 'accounting-firm',
            'printing-branding' => 'printing_branding',
            'printingbranding' => 'printing_branding',
        ][$slug] ?? ($slug ?: null);
    }

    public function menuKey(array $item): string
    {
        $key = (string) ($item['route'] ?? $item['label'] ?? '');

        if (! empty($item['section'])) {
            $key .= '#'.$item['section'];
        }

        return $key;
    }

    public function preferences(User $user, ?string $industry): array
    {
        $industry = $this->normalizeIndustry($industry);
        $layout = $user->dashboard_layout ?? [];

        return $industry ? data_get($layout, 'industry_preferences.'.$industry, []) : [];
    }

    public function hiddenMenuKeys(User $user, ?string $industry): array
    {
        return collect($this->preferences($user, $industry)['hidden_menu_keys'] ?? [])
            ->filter()
            ->values()
            ->all();
    }

    public function hiddenWidgetSlugs(User $user, ?string $industry): array
    {
        return collect($this->preferences($user, $industry)['hidden_widget_slugs'] ?? [])
            ->filter()
            ->values()
            ->all();
    }

    public function filterMenus(Collection $items, ?User $user, ?string $industry): Collection
    {
        if (! $user) {
            return $items->values();
        }

        $hidden = $this->hiddenMenuKeys($user, $industry);

        if ($hidden === []) {
            return $items->values();
        }

        return $items
            ->reject(fn (array $item) => ! $this->isProtectedMenu($item) && in_array($this->menuKey($item), $hidden, true))
            ->values();
    }

    public function filterWidgets(Collection $widgets, ?User $user, ?string $industry): Collection
    {
        if (! $user) {
            return $widgets->values();
        }

        $hidden = $this->hiddenWidgetSlugs($user, $industry);

        if ($hidden === []) {
            return $widgets->values();
        }

        return $widgets
            ->reject(fn ($widget) => in_array((string) $widget->slug, $hidden, true))
            ->values();
    }

    public function save(User $user, ?string $industry, array $preferences): void
    {
        $industry = $this->normalizeIndustry($industry);

        if (! $industry) {
            return;
        }

        $layout = $user->dashboard_layout ?? [];
        $all = data_get($layout, 'industry_preferences', []);
        $all[$industry] = [
            'hidden_menu_keys' => collect($preferences['hidden_menu_keys'] ?? [])->filter()->values()->all(),
            'hidden_widget_slugs' => collect($preferences['hidden_widget_slugs'] ?? [])->filter()->values()->all(),
            'component_density' => $preferences['component_density'] ?? 'comfortable',
        ];

        data_set($layout, 'industry_preferences', $all);
        $user->forceFill(['dashboard_layout' => $layout])->save();
    }

    private function isProtectedMenu(array $item): bool
    {
        return in_array($item['label'] ?? null, ['Dashboard', 'Settings', 'Administration', 'Bama Billing'], true);
    }
}
