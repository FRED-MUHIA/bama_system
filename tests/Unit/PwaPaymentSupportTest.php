<?php

namespace Tests\Unit;

use Tests\TestCase;

class PwaPaymentSupportTest extends TestCase
{
    public function test_the_installed_web_app_bypasses_its_cache_for_billing_and_payment_requests(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const BAMA_SW_VERSION = 'bama-pwa-v12'", $serviceWorker);
        $this->assertStringContainsString("if (request.method !== 'GET') return;", $serviceWorker);
        $this->assertStringContainsString("'/billing'", $serviceWorker);
        $this->assertStringContainsString("'/images/optimized/'", $serviceWorker);
        $this->assertStringContainsString('if (isPrivatePath(url.pathname))', $serviceWorker);
        $this->assertStringNotContainsString('analytics-command-center.png', $serviceWorker);
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

    public function test_splash_launcher_icons_keep_compact_artwork(): void
    {
        if (! function_exists('imagecreatefrompng')) {
            $this->markTestSkipped('GD extension is required to inspect launcher icon bounds.');
        }

        $this->assertVisibleIconBounds(public_path('pwa-icons/icon-192.png'), 70, 95, 90, 112);
        $this->assertVisibleIconBounds(public_path('pwa-icons/icon-512.png'), 195, 250, 225, 280);
        $this->assertVisibleIconBounds(public_path('pwa-icons/maskable-192.png'), 70, 95, 90, 112);
        $this->assertVisibleIconBounds(public_path('pwa-icons/maskable-512.png'), 195, 250, 225, 280);
    }

    private function assertVisibleIconBounds(string $path, int $minimumWidth, int $minimumHeight, int $maximumWidth, int $maximumHeight): void
    {
        [$visibleWidth, $visibleHeight] = $this->visibleIconBounds($path);

        $this->assertGreaterThanOrEqual($minimumWidth, $visibleWidth, $path.' visible width');
        $this->assertGreaterThanOrEqual($minimumHeight, $visibleHeight, $path.' visible height');
        $this->assertLessThanOrEqual($maximumWidth, $visibleWidth, $path.' visible width');
        $this->assertLessThanOrEqual($maximumHeight, $visibleHeight, $path.' visible height');
    }

    private function visibleIconBounds(string $path): array
    {
        $image = imagecreatefrompng($path);
        $width = imagesx($image);
        $height = imagesy($image);
        $minX = $width;
        $maxX = -1;
        $minY = $height;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($image, $x, $y) & 0x7F000000) >> 24;

                if ($alpha < 127) {
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
        }

        imagedestroy($image);

        return [$maxX - $minX + 1, $maxY - $minY + 1];
    }
}
