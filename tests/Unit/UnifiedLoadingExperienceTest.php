<?php

namespace Tests\Unit;

use Tests\TestCase;

class UnifiedLoadingExperienceTest extends TestCase
{
    public function test_shared_shell_uses_the_compact_branded_splash_loader(): void
    {
        $shell = file_get_contents(resource_path('views/mobile/pwa-shell.blade.php'));
        $styles = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('data-bama-loader', $shell);
        $this->assertStringContainsString('bama-radial-loader', $shell);
        $this->assertStringContainsString('bama-splash-content', $shell);
        $this->assertStringContainsString('Business Management Anywhere', $shell);
        $this->assertStringContainsString('<x-bama-logo variant="splash"', $shell);
        $this->assertStringContainsString('$spoke < 8', $shell);
        $this->assertStringContainsString('bama-initial-loader-shown', $shell);
        $this->assertStringContainsString('sessionStorage.getItem(sessionKey)', $shell);
        $this->assertStringContainsString('<noscript><style>.bama-loading-screen { display: none !important; }</style></noscript>', $shell);
        $this->assertStringContainsString('background: #007A3B', $styles);
        $this->assertStringContainsString('width: 42px', $styles);
        $this->assertStringContainsString('height: 10px', $styles);
        $this->assertStringContainsString('transform-origin: 3px 19px', $styles);
        $this->assertStringContainsString('background: #7ed342', $styles);
        $this->assertStringContainsString('background: rgba(255, 255, 255, .48)', $styles);
        $this->assertStringContainsString('@keyframes bama-loader-pulse', $styles);

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

    public function test_new_pwa_builds_activate_and_reload_automatically(): void
    {
        $meta = file_get_contents(resource_path('views/mobile/pwa-meta.blade.php'));
        $shell = file_get_contents(resource_path('views/mobile/pwa-shell.blade.php'));
        $script = file_get_contents(resource_path('js/pwa.js'));
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('bama-build-version', $meta);
        $this->assertStringContainsString("hash_file('sha256', \$viteManifest)", $meta);
        $this->assertStringContainsString('function configureAutomaticAppUpdates(registration)', $script);
        $this->assertStringContainsString('registration.update()', $script);
        $this->assertStringContainsString('APP_UPDATE_INTERVAL', $script);
        $this->assertStringContainsString("worker?.postMessage({ type: 'SKIP_WAITING' })", $script);
        $this->assertStringContainsString('controllerRefreshing', $script);
        $this->assertStringContainsString('window.location.reload()', $script);
        $this->assertStringContainsString('bama-pwa-v5', $worker);
        $this->assertStringNotContainsString('data-bama-update-now', $shell.$script);
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
