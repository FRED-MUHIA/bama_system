<?php

namespace Tests\Unit;

use Tests\TestCase;

class PwaPaymentSupportTest extends TestCase
{
    public function test_the_installed_web_app_bypasses_its_cache_for_billing_and_payment_requests(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const BAMA_SW_VERSION = 'bama-pwa-v6'", $serviceWorker);
        $this->assertStringContainsString("if (request.method !== 'GET') return;", $serviceWorker);
        $this->assertStringContainsString("'/billing'", $serviceWorker);
        $this->assertStringContainsString('if (isPrivatePath(url.pathname))', $serviceWorker);
    }

    public function test_the_installed_web_app_exposes_a_billing_shortcut(): void
    {
        $manifest = json_decode(
            file_get_contents(public_path('manifest.webmanifest')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertTrue(collect($manifest['shortcuts'] ?? [])->contains(
            fn (array $shortcut): bool => ($shortcut['url'] ?? null) === '/billing'
                && ($shortcut['short_name'] ?? null) === 'Pay'
        ));
    }
}
