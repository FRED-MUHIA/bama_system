<?php

namespace Tests\Unit;

use Tests\TestCase;

class UnifiedLoadingExperienceTest extends TestCase
{
    public function test_shared_shell_uses_the_simple_green_radial_loader(): void
    {
        $shell = file_get_contents(resource_path('views/mobile/pwa-shell.blade.php'));
        $styles = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('data-bama-loader', $shell);
        $this->assertStringContainsString('bama-radial-loader', $shell);
        $this->assertStringContainsString('$spoke < 8', $shell);
        $this->assertStringContainsString('bama-initial-loader-shown', $shell);
        $this->assertStringContainsString('sessionStorage.getItem(sessionKey)', $shell);
        $this->assertStringContainsString('<noscript><style>.bama-loading-screen { display: none !important; }</style></noscript>', $shell);
        $this->assertStringContainsString('background: #fff', $styles);
        $this->assertStringContainsString('width: 64px', $styles);
        $this->assertStringContainsString('background: #7ed342', $styles);
        $this->assertStringContainsString('background: #349b36', $styles);
        $this->assertStringContainsString('@keyframes bama-loader-pulse', $styles);

        $this->assertStringNotContainsString('bama-pwa-splash-mark', $shell.$styles);
        $this->assertStringNotContainsString('bama-pwa-splash-name', $shell.$styles);
        $this->assertStringNotContainsString('bama-pwa-splash-tagline', $shell.$styles);
    }

    public function test_loader_is_not_reopened_for_every_navigation(): void
    {
        $script = file_get_contents(resource_path('js/pwa.js'));

        $this->assertStringContainsString('function configurePageLoader()', $script);
        $this->assertStringContainsString('window.BamaLoader', $script);
        $this->assertStringNotContainsString("document.addEventListener('click'", $script);
        $this->assertStringNotContainsString("document.addEventListener('submit'", $script);
        $this->assertStringNotContainsString("window.addEventListener('beforeunload'", $script);
        $this->assertStringNotContainsString('configureSplash', $script);
        $this->assertStringNotContainsString("splash.remove()", $script);
    }

    public function test_old_payment_and_login_loading_icons_are_removed(): void
    {
        $views = implode("\n", [
            file_get_contents(resource_path('views/auth/app-login.blade.php')),
            file_get_contents(resource_path('views/billing/index.blade.php')),
            file_get_contents(resource_path('views/billing/card.blade.php')),
            file_get_contents(resource_path('views/platform/payments.blade.php')),
        ]);

        $this->assertStringNotContainsString('app-loading-overlay', $views);
        $this->assertStringNotContainsString('app-loading-ring', $views);
        $this->assertStringNotContainsString('app-submit-spinner', $views);
        $this->assertStringNotContainsString('spinner-border', $views);
        $this->assertStringNotContainsString('bi-hourglass-split', $views);
    }
}
