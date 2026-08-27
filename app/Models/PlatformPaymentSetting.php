<?php

namespace App\Models;

use App\Casts\TolerantEncryptedArray;
use App\Casts\TolerantEncryptedString;
use Illuminate\Database\Eloquent\Model;

class PlatformPaymentSetting extends Model
{
    protected $fillable = ['provider', 'is_enabled', 'mode', 'public_key', 'secret_key', 'config', 'instructions'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'secret_key' => TolerantEncryptedString::class,
            'config' => TolerantEncryptedArray::class,
        ];
    }
}
