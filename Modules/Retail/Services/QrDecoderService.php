<?php

namespace Modules\Retail\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class QrDecoderService
{
    public function __construct(private ?ImageCodeDecoderService $imageDecoder = null)
    {
        $this->imageDecoder ??= new ImageCodeDecoderService();
    }

    public function decode(array $input): array
    {
        $raw = trim((string) ($input['raw_value'] ?? $input['scanner_input'] ?? $input['decoded_text'] ?? ''));
        $imageDecode = $this->imageDecoder->decode($input);

        if ($raw === '' && ! empty($input['barcode_image']) && $input['barcode_image'] instanceof UploadedFile) {
            $raw = pathinfo($input['barcode_image']->getClientOriginalName(), PATHINFO_FILENAME);
        }

        if ($raw === '' && ! empty($input['image_reference'])) {
            $raw = (string) $input['image_reference'];
        }

        if ($raw === '' && ! empty($imageDecode['primary_code'])) {
            $raw = (string) $imageDecode['primary_code'];
        }

        $payload = $this->parsePayload($raw);
        $identifier = $this->identifier($payload, $raw, $imageDecode['candidates'] ?? []);
        $payload = array_filter($payload + [
            '_decoded_text' => $imageDecode['decoded_text'] ?? null,
            '_detected_codes' => array_map(fn ($candidate) => $candidate['value'], $imageDecode['candidates'] ?? []),
            '_image_path' => $imageDecode['stored_image_path'] ?? null,
            '_decode_sources' => $imageDecode['all_text'] ?? [],
        ], fn ($value) => $value !== null && $value !== []);

        return [
            'raw_value' => $raw,
            'symbology' => $input['symbology'] ?? $payload['symbology'] ?? $this->guessSymbology($raw),
            'identifier_type' => $identifier[0],
            'identifier_value' => $identifier[1],
            'payload' => $payload,
        ];
    }

    private function parsePayload(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }

        $pairs = [];
        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            parse_str((string) parse_url($raw, PHP_URL_QUERY), $query);
            $pairs = array_merge($pairs, array_change_key_case($query, CASE_LOWER));
        }

        foreach (preg_split('/[|;]/', $raw) ?: [] as $part) {
            if (! str_contains($part, '=') && ! str_contains($part, ':')) {
                continue;
            }
            $separator = str_contains($part, '=') ? '=' : ':';
            [$key, $value] = array_map('trim', explode($separator, $part, 2));
            $pairs[Str::snake($key)] = $value;
        }

        return $pairs;
    }

    private function identifier(array $payload, string $raw, array $candidates = []): array
    {
        foreach (['sku', 'barcode', 'qr_product_code', 'gtin', 'upc', 'ean', 'internal_product_number', 'product_code'] as $key) {
            if (! empty($payload[$key])) {
                return [$key, (string) $payload[$key]];
            }
        }

        foreach ($candidates as $candidate) {
            if (! empty($candidate['value'])) {
                return [(string) $candidate['type'], (string) $candidate['value']];
            }
        }

        return [$this->guessIdentifierType($raw), $raw];
    }

    private function guessIdentifierType(string $raw): string
    {
        return match (strlen(preg_replace('/\D/', '', $raw))) {
            8, 13 => 'ean',
            12 => 'upc',
            14 => 'gtin',
            default => str_starts_with(Str::upper($raw), 'SKU') ? 'sku' : 'barcode',
        };
    }

    private function guessSymbology(string $raw): string
    {
        if (str_starts_with(trim($raw), '{') || str_contains($raw, 'qr_product_code=')) {
            return 'QR';
        }

        $digits = preg_replace('/\D/', '', $raw);

        return strlen($digits) > 12 ? '2D Barcode' : '1D Barcode';
    }
}
