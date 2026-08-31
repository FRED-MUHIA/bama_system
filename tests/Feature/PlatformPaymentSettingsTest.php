<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_fetch_live_kes_usd_rate(): void
    {
        Cache::forget('exchange-rates.usd-kes');
        config(['services.exchange_rates.usd_kes_url' => 'https://rates.test/usd-kes']);

        Http::fake([
            'https://rates.test/usd-kes' => Http::response([
                'date' => '2026-08-31',
                'base' => 'USD',
                'quote' => 'KES',
                'rate' => 129.73456,
            ]),
        ]);

        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($user)
            ->getJson(route('platform.payment-settings.kes-usd-rate'))
            ->assertOk()
            ->assertJson([
                'base' => 'USD',
                'quote' => 'KES',
                'rate' => 129.7346,
                'date' => '2026-08-31',
                'source' => 'rates.test',
            ]);
    }

    public function test_live_kes_usd_rate_reports_provider_failure(): void
    {
        Cache::forget('exchange-rates.usd-kes');
        config(['services.exchange_rates.usd_kes_url' => 'https://rates.test/usd-kes']);

        Http::fake([
            'https://rates.test/usd-kes' => Http::response(['message' => 'Downstream error'], 503),
        ]);

        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($user)
            ->getJson(route('platform.payment-settings.kes-usd-rate'))
            ->assertStatus(502)
            ->assertJson(['message' => 'Live USD to KES exchange rate is unavailable right now.']);
    }
}
