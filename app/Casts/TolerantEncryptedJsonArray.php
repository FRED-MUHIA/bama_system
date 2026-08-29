<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class TolerantEncryptedJsonArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $this->decryptPayload($value) ?? $value;
        }

        $decoded = $this->decode((string) $value);
        if (is_array($decoded)) {
            return $this->decryptPayload($decoded) ?? $decoded;
        }

        if (is_string($decoded)) {
            return $this->decryptStringPayload($decoded);
        }

        return $this->decryptStringPayload((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $json = json_encode($value, JSON_THROW_ON_ERROR);

        return json_encode(['_encrypted' => Crypt::encryptString($json)], JSON_THROW_ON_ERROR);
    }

    private function decryptPayload(array $value): ?array
    {
        $payload = $value['_encrypted'] ?? null;

        return is_string($payload) ? $this->decryptStringPayload($payload) : null;
    }

    private function decryptStringPayload(string $value): ?array
    {
        try {
            $value = Crypt::decryptString($value);
        } catch (DecryptException) {
            try {
                $value = Crypt::decrypt($value);
            } catch (DecryptException) {
                return null;
            }
        }

        $decoded = $this->decode((string) $value);

        return is_array($decoded) ? $decoded : null;
    }

    private function decode(string $value): mixed
    {
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
