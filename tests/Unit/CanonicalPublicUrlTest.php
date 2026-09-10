<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CanonicalPublicUrlTest extends TestCase
{
    #[DataProvider('exposedPublicUrls')]
    public function test_it_canonicalizes_requests_to_an_exposed_public_directory(
        array $server,
        string $expected,
    ): void {
        $canonicalPublicUrl = require base_path('bootstrap/canonical-public-url.php');

        $this->assertSame($expected, $canonicalPublicUrl($server));
    }

    public static function exposedPublicUrls(): array
    {
        return [
            'public without trailing slash' => [
                ['SCRIPT_NAME' => '/public/index.php', 'REQUEST_URI' => '/public'],
                '/',
            ],
            'public with trailing slash' => [
                ['SCRIPT_NAME' => '/public/index.php', 'REQUEST_URI' => '/public/'],
                '/',
            ],
            'nested path and query string' => [
                [
                    'SCRIPT_NAME' => '/public/index.php',
                    'REQUEST_URI' => '/public/login?next=%2Fdashboard',
                    'QUERY_STRING' => 'next=%2Fdashboard',
                ],
                '/login?next=%2Fdashboard',
            ],
            'application installed below a domain subdirectory' => [
                [
                    'SCRIPT_NAME' => '/bama/public/index.php',
                    'REQUEST_URI' => '/bama/public/invoice/example-token',
                ],
                '/bama/invoice/example-token',
            ],
        ];
    }

    #[DataProvider('canonicalUrls')]
    public function test_it_leaves_canonical_and_internally_rewritten_requests_unchanged(array $server): void
    {
        $canonicalPublicUrl = require base_path('bootstrap/canonical-public-url.php');

        $this->assertNull($canonicalPublicUrl($server));
    }

    public static function canonicalUrls(): array
    {
        return [
            'public is the configured document root' => [[
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/',
            ]],
            'project root htaccess rewrote the request internally' => [[
                'SCRIPT_NAME' => '/public/index.php',
                'REQUEST_URI' => '/',
            ]],
            'ordinary application route' => [[
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/login',
            ]],
        ];
    }
}
