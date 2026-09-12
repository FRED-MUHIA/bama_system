<?php

namespace Tests\Feature;

use App\Models\PlatformPaymentSetting;
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

    public function test_mpesa_payment_key_form_uses_clear_daraja_api_labels(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($user)
            ->get(route('platform.payments'))
            ->assertOk()
            ->assertSee('Safaricom Daraja Consumer Key / API Key')
            ->assertSee('Safaricom Daraja Consumer Secret / API Secret')
            ->assertSee('Do not mix sandbox keys with Live mode or live keys with Sandbox mode.')
            ->assertSee('STK Push passkey');
    }

    public function test_owner_can_update_mpesa_keys_and_keep_saved_values_when_blanks_are_submitted(): void
    {
        PlatformPaymentSetting::updateOrCreate(
            ['provider' => 'mpesa'],
            [
                'is_enabled' => true,
                'mode' => 'sandbox',
                'public_key' => 'old-consumer-key',
                'secret_key' => 'old-consumer-secret',
                'config' => [
                    'shortcode' => '174379',
                    'passkey' => 'old-passkey',
                    'callback_url' => 'https://old.example.test/mpesa/callback',
                    'transaction_type' => 'CustomerPayBillOnline',
                ],
            ]
        );

        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($user)
            ->from(route('platform.payments'))
            ->put(route('platform.payment-settings.update'), [
                'providers' => [
                    'mpesa' => [
                        'is_enabled' => '1',
                        'mode' => 'live',
                        'public_key' => ' new-consumer-key ',
                        'secret_key' => ' new-consumer-secret ',
                        'config' => [
                            'shortcode' => '4214033',
                            'passkey' => ' new-passkey ',
                            'callback_url' => ' https://bama.co.ke/billing/mpesa/callback ',
                            'transaction_type' => 'CustomerPayBillOnline',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('platform.payments'))
            ->assertSessionHasNoErrors();

        $setting = PlatformPaymentSetting::where('provider', 'mpesa')->firstOrFail();
        $this->assertSame('live', $setting->mode);
        $this->assertSame('new-consumer-key', $setting->public_key);
        $this->assertSame('new-consumer-secret', $setting->secret_key);
        $this->assertSame('4214033', $setting->config['shortcode']);
        $this->assertSame('new-passkey', $setting->config['passkey']);
        $this->assertSame('https://bama.co.ke/billing/mpesa/callback', $setting->config['callback_url']);

        $this->actingAs($user)
            ->from(route('platform.payments'))
            ->put(route('platform.payment-settings.update'), [
                'providers' => [
                    'mpesa' => [
                        'is_enabled' => '1',
                        'mode' => 'live',
                        'public_key' => '',
                        'secret_key' => '',
                        'config' => [
                            'shortcode' => '4214033',
                            'passkey' => '',
                            'callback_url' => 'https://bama.co.ke/billing/mpesa/callback',
                            'transaction_type' => 'CustomerPayBillOnline',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('platform.payments'))
            ->assertSessionHasNoErrors();

        $setting = PlatformPaymentSetting::where('provider', 'mpesa')->firstOrFail();
        $this->assertSame('new-consumer-key', $setting->public_key);
        $this->assertSame('new-consumer-secret', $setting->secret_key);
        $this->assertSame('new-passkey', $setting->config['passkey']);
    }

    public function test_enabled_mpesa_settings_require_complete_live_prompt_configuration(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'status' => 'Active',
        ]);

        $this->actingAs($user)
            ->from(route('platform.payments'))
            ->put(route('platform.payment-settings.update'), [
                'providers' => [
                    'mpesa' => [
                        'is_enabled' => '1',
                        'mode' => 'live',
                        'public_key' => 'consumer-key',
                        'secret_key' => 'consumer-secret',
                        'config' => [
                            'shortcode' => 'paybill-123',
                            'passkey' => '',
                            'callback_url' => 'http://bama.test/api/payments/mpesa/callback',
                            'transaction_type' => 'CustomerPayBillOnline',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('platform.payments'))
            ->assertSessionHasErrors([
                'providers.mpesa.config.shortcode',
                'providers.mpesa.config.passkey',
                'providers.mpesa.config.callback_url',
            ]);
    }
}
