<?php

namespace Tests\Unit;

use Tests\TestCase;

class LightModeOnlyTest extends TestCase
{
    public function test_application_layout_is_locked_to_light_mode(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('<html lang="en" data-theme="light">', $layout);
        $this->assertStringContainsString('<meta name="color-scheme" content="light">', $layout);
        $this->assertStringNotContainsString('bama-theme', $layout);
        $this->assertStringNotContainsString('data-theme-toggle', $layout);
        $this->assertStringNotContainsString('data-theme="dark"', $layout);
        $this->assertStringNotContainsString('prefers-color-scheme:dark', $layout);
    }

    public function test_module_and_pwa_chrome_do_not_offer_a_dark_appearance(): void
    {
        $printingDashboard = file_get_contents(resource_path('views/printing-branding/dashboard.blade.php'));
        $pwaMeta = file_get_contents(resource_path('views/mobile/pwa-meta.blade.php'));

        $this->assertStringNotContainsString('data-theme="dark"', $printingDashboard);
        $this->assertStringContainsString('apple-mobile-web-app-status-bar-style" content="default', $pwaMeta);
        $this->assertStringNotContainsString('black-translucent', $pwaMeta);
    }

    public function test_production_styles_do_not_contain_dark_mode_media_rules(): void
    {
        $manifest = json_decode(
            file_get_contents(public_path('build/manifest.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;

        $this->assertNotEmpty($cssFile);
        $css = file_get_contents(public_path('build/'.$cssFile));

        $this->assertStringContainsString('color-scheme:light', $css);
        $this->assertStringNotContainsString('prefers-color-scheme:dark', $css);
    }
}
