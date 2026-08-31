<?php

namespace Tests\Unit;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    public function test_it_fetches_and_normalizes_usd_to_kes_rate(): void
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

        $quote = app(ExchangeRateService::class)->usdToKes(refresh: true);

        $this->assertSame('USD', $quote['base']);
        $this->assertSame('KES', $quote['quote']);
        $this->assertSame(129.7346, $quote['rate']);
        $this->assertSame('2026-08-31', $quote['date']);
        $this->assertSame('rates.test', $quote['source']);
    }

    public function test_it_reports_unsuccessful_provider_responses(): void
    {
        Cache::forget('exchange-rates.usd-kes');
        config(['services.exchange_rates.usd_kes_url' => 'https://rates.test/usd-kes']);

        Http::fake([
            'https://rates.test/usd-kes' => Http::response(['message' => 'Downstream error'], 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Live USD to KES exchange rate is unavailable right now.');

        app(ExchangeRateService::class)->usdToKes(refresh: true);
    }
}
