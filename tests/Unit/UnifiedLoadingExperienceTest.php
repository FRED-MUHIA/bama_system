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
        $this->assertStringContainsString('$spoke < 16', $shell);
        $this->assertStringContainsString('<noscript><style>.bama-loading-screen { display: none !important; }</style></noscript>', $shell);
        $this->assertStringContainsString('background: #fff', $styles);
        $this->assertStringContainsString('background: #00c81f', $styles);
        $this->assertStringContainsString('@keyframes bama-loader-pulse', $styles);

        $this->assertStringNotContainsString('bama-pwa-splash-mark', $shell.$styles);
        $this->assertStringNotContainsString('bama-pwa-splash-name', $shell.$styles);
        $this->assertStringNotContainsString('bama-pwa-splash-tagline', $shell.$styles);
    }

    public function test_loader_runs_for_browser_and_installed_app_navigation(): void
    {
        $script = file_get_contents(resource_path('js/pwa.js'));

        $this->assertStringContainsString('function configurePageLoader()', $script);
        $this->assertStringContainsString('window.BamaLoader', $script);
        $this->assertStringContainsString("document.addEventListener('click'", $script);
        $this->assertStringContainsString("document.addEventListener('submit'", $script);
        $this->assertStringContainsString("window.addEventListener('beforeunload'", $script);
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
