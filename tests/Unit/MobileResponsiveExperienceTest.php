<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobileResponsiveExperienceTest extends TestCase
{
    public function test_authentication_surfaces_share_the_responsive_layout_and_logo(): void
    {
        $views = collect([
            'auth/app-login.blade.php',
            'auth/login.blade.php',
            'auth/forgot-password.blade.php',
            'auth/reset-password.blade.php',
            'auth/verify-email.blade.php',
            'components/registration-shell.blade.php',
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
}
