<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobileResponsiveExperienceTest extends TestCase
{
    public function test_app_authentication_surfaces_share_the_responsive_layout_and_logo(): void
    {
        $views = collect([
            'auth/app-login.blade.php',
            'auth/forgot-password.blade.php',
            'auth/reset-password.blade.php',
            'auth/verify-email.blade.php',
            'onboarding/tenant.blade.php',
        ])->mapWithKeys(fn (string $view) => [
            $view => file_get_contents(resource_path('views/'.$view)),
        ]);

        foreach ($views as $view => $contents) {
            $this->assertStringContainsString('x-auth-layout', $contents, $view);
        }

        $logo = file_get_contents(resource_path('views/components/bama-logo.blade.php'));
        $styles = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString("'bama-brand-logo--'.\$variant", $logo);
        $this->assertStringContainsString('object-fit: contain', $styles);
        $this->assertStringContainsString('width: clamp(7.5rem, 38vw, 9.375rem)', $styles);
        $this->assertStringContainsString('max-width: 420px', $styles);
        $this->assertStringContainsString('max-width: 440px', $styles);
    }

    public function test_website_login_and_registration_use_the_marketing_layout(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $account = file_get_contents(resource_path('views/registration/account.blade.php'));
        $registrationShell = file_get_contents(resource_path('views/components/registration-shell.blade.php'));

        $this->assertStringContainsString("extends('layouts.marketing'", $login);
        $this->assertStringContainsString("extends('layouts.marketing'", $account);
        $this->assertStringNotContainsString('x-auth-layout', $login.$registrationShell);
        $this->assertStringNotContainsString('route(\'app.login\')', $account);
        $this->assertStringContainsString('route(\'login\')', $account);
    }

    public function test_mobile_viewport_keyboard_safe_area_and_touch_contracts_are_present(): void
    {
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $marketingLayout = file_get_contents(resource_path('views/layouts/marketing.blade.php'));
        $styles = file_get_contents(resource_path('css/app.css'));
        $script = file_get_contents(resource_path('js/app.js'));
        $appLogin = file_get_contents(resource_path('views/auth/app-login.blade.php'));

        $this->assertStringContainsString('viewport-fit=cover', $appLayout);
        $this->assertStringContainsString('interactive-widget=resizes-content', $marketingLayout);
        $this->assertStringContainsString('env(safe-area-inset-top)', $styles);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $styles);
        $this->assertStringContainsString('--bama-visual-viewport-height', $styles.$script);
        $this->assertStringContainsString("scrollIntoView({ block: 'nearest'", $script);
        $this->assertStringContainsString('min-height: 44px', $styles);
        $this->assertStringContainsString(':focus-visible', $styles);
        $this->assertStringNotContainsString('font-size:clamp(4.5rem,9vw,8rem)', $appLogin);
        $this->assertStringNotContainsString('min-height:62px', $appLogin);
    }

    public function test_retail_mobile_quick_add_replaces_generic_actions(): void
    {
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        foreach (['Add Customer', 'Place Order', 'Add Products', 'Stocks Records', 'Receipts'] as $label) {
            $this->assertStringContainsString("'label' => '{$label}'", $appLayout);
        }

        $this->assertStringContainsString("'fragment' => 'retail-add-customer'", $appLayout);
        $this->assertStringContainsString("'fragment' => 'retail-place-order'", $appLayout);
        $this->assertStringContainsString("'fragment' => 'retail-add-product'", $appLayout);
        $this->assertStringContainsString("'fragment' => 'retail-stock-records'", $appLayout);
        $this->assertStringContainsString('$isRetailContext ? $retailQuickActions : $defaultQuickActions', $appLayout);
    }
}
