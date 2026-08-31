<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateService
{
    public function usdToKes(bool $refresh = false): array
    {
        $cacheKey = 'exchange-rates.usd-kes';

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $quote = $this->fetchUsdToKes();
        Cache::put($cacheKey, $quote, now()->addMinutes((int) config('services.exchange_rates.cache_minutes', 30)));

        return $quote;
    }

    private function fetchUsdToKes(): array
    {
        $url = config('services.exchange_rates.usd_kes_url', 'https://api.frankfurter.dev/v2/rate/USD/KES');

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->retry(2, 250)
                ->get($url);
        } catch (ConnectionException|RequestException) {
            throw new RuntimeException('Live USD to KES exchange rate is unavailable right now.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Live USD to KES exchange rate is unavailable right now.');
        }

        $payload = $response->json();
        $rate = data_get($payload, 'rate')
            ?? data_get($payload, 'rates.KES')
            ?? data_get($payload, 'conversion_rate');

        if (! is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException('Live USD to KES exchange rate response was invalid.');
        }

        return [
            'base' => data_get($payload, 'base', data_get($payload, 'base_code', 'USD')),
            'quote' => data_get($payload, 'quote', 'KES'),
            'rate' => round((float) $rate, 4),
            'date' => data_get($payload, 'date'),
            'source' => parse_url($url, PHP_URL_HOST) ?: 'exchange-rate-provider',
        ];
    }
}
