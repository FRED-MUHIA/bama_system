<?php

namespace Modules\Retail\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Zxing\QrReader;

class ImageCodeDecoderService
{
    public function decode(array $input): array
    {
        $texts = $this->seedTexts($input);
        $file = $this->uploadedFile($input);
        $imagePath = $input['image_path'] ?? null;

        if ($file) {
            $imagePath = $imagePath ?: $file->store('retail/scans', 'public');

            if ($text = $this->readEmbeddedText($file)) {
                $texts[] = $text;
            }

            if ($qrText = $this->decodeQrImage($file)) {
                $texts[] = $qrText;
            }

            $texts[] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        $texts = collect($texts)
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $candidates = $this->extractCandidates($texts);

        return [
            'stored_image_path' => $imagePath,
            'decoded_text' => $texts[0] ?? null,
            'all_text' => $texts,
            'candidates' => $candidates,
            'primary_code' => $candidates[0]['value'] ?? ($texts[0] ?? null),
        ];
    }

    private function seedTexts(array $input): array
    {
        return array_filter([
            $input['raw_value'] ?? null,
            $input['scanner_input'] ?? null,
            $input['decoded_text'] ?? null,
            $input['manual_code'] ?? null,
            $input['image_reference'] ?? null,
        ], fn ($value) => is_scalar($value) && trim((string) $value) !== '');
    }

    private function uploadedFile(array $input): ?UploadedFile
    {
        foreach (['barcode_image', 'camera_image', 'image'] as $key) {
            if (($input[$key] ?? null) instanceof UploadedFile) {
                return $input[$key];
            }
        }

        return null;
    }

    private function readEmbeddedText(UploadedFile $file): ?string
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $mime = Str::lower((string) $file->getMimeType());

        if (! in_array($extension, ['svg', 'txt', 'csv', 'json', 'xml'], true)
            && ! Str::contains($mime, ['text', 'json', 'xml', 'svg'])) {
            return null;
        }

        $contents = @file_get_contents($file->getRealPath());
        if (! is_string($contents) || trim($contents) === '') {
            return null;
        }

        return html_entity_decode(strip_tags($contents));
    }

    private function decodeQrImage(UploadedFile $file): ?string
    {
        if (! class_exists(QrReader::class) || Str::lower($file->getClientOriginalExtension()) === 'svg') {
            return null;
        }

        try {
            $reader = new QrReader($file->getRealPath(), QrReader::SOURCE_TYPE_FILE, false);
            $text = $reader->text();

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function extractCandidates(array $texts): array
    {
        $candidates = [];

        foreach ($texts as $text) {
            foreach ($this->structuredCandidates($text) as $candidate) {
                $candidates[] = $candidate;
            }

            $normalizedGs1 = str_replace(['(', ')'], '', $text);
            if (preg_match_all('/(?:^|\D)01(\d{14})(?:\D|$)/', $normalizedGs1, $matches)) {
                foreach ($matches[1] as $value) {
                    $candidates[] = ['type' => 'gtin', 'value' => $value, 'source' => 'gs1'];
                }
            }

            if (preg_match_all('/\b\d{6,18}\b/', $text, $matches)) {
                foreach ($matches[0] as $value) {
                    $candidates[] = ['type' => $this->guessIdentifierType($value), 'value' => $value, 'source' => 'numeric'];
                }
            }

            if (preg_match_all('/\b[A-Z][A-Z0-9._-]{2,}\b/i', $text, $matches)) {
                foreach ($matches[0] as $value) {
                    if (! in_array(Str::lower($value), ['barcode', 'product', 'sku', 'code', 'image', 'scan', 'text'], true)) {
                        $candidates[] = ['type' => str_starts_with(Str::upper($value), 'SKU') ? 'sku' : 'barcode', 'value' => $value, 'source' => 'token'];
                    }
                }
            }
        }

        return collect($candidates)
            ->filter(fn ($candidate) => trim((string) $candidate['value']) !== '')
            ->unique(fn ($candidate) => $candidate['type'].'|'.$candidate['value'])
            ->sortBy(fn ($candidate) => $this->sourcePriority($candidate['source'] ?? 'token'))
            ->values()
            ->all();
    }

    private function structuredCandidates(string $text): array
    {
        $candidates = [];
        $json = json_decode($text, true);

        if (is_array($json)) {
            foreach ($this->identifierKeys() as $key) {
                if (! empty($json[$key])) {
                    $candidates[] = ['type' => $key, 'value' => (string) $json[$key], 'source' => 'json'];
                }
            }
        }

        if (filter_var($text, FILTER_VALIDATE_URL)) {
            parse_str((string) parse_url($text, PHP_URL_QUERY), $query);
            foreach ($this->identifierKeys() as $key) {
                if (! empty($query[$key])) {
                    $candidates[] = ['type' => $key, 'value' => (string) $query[$key], 'source' => 'url'];
                }
            }
        }

        $pattern = '/\b('.implode('|', $this->identifierKeys()).')\b\s*[:=]\s*"?([A-Z0-9._-]{3,})"?/i';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $candidates[] = ['type' => Str::snake($match[1]), 'value' => $match[2], 'source' => 'field'];
            }
        }

        return $candidates;
    }

    private function identifierKeys(): array
    {
        return ['sku', 'barcode', 'qr_product_code', 'gtin', 'upc', 'ean', 'internal_product_number', 'product_code'];
    }

    private function sourcePriority(string $source): int
    {
        return match ($source) {
            'json', 'url', 'field' => 0,
            'gs1' => 1,
            'numeric' => 2,
            default => 9,
        };
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
}
