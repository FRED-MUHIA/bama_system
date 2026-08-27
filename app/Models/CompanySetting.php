<?php

namespace App\Models;

use App\Casts\TolerantEncryptedString;
use App\Models\Concerns\BelongsToBusiness;
use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use BelongsToBusiness;

    public const DEFAULT_PRIMARY_COLOR = '#00A651';

    public const DEFAULT_SECONDARY_COLOR = '#111827';

    public const DEFAULT_ACCENT_COLOR = '#E7F8EF';

    protected $fillable = [
        'business_id', 'company_name', 'logo_path', 'primary_color', 'secondary_color', 'accent_color', 'phone', 'email', 'address', 'website',
        'location', 'tax_name', 'tax_rate', 'currency_code', 'locale', 'default_terms',
    ];

    protected function casts(): array
    {
        return [
            'address' => TolerantEncryptedString::class,
            'default_terms' => TolerantEncryptedString::class,
        ];
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return PublicUpload::url($this->logo_path);
    }

    public function logoFilePath(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return PublicUpload::filePath($this->logo_path);
    }

    public function documentColors(): array
    {
        return [
            'primary' => $this->hexColor($this->primary_color, self::DEFAULT_PRIMARY_COLOR),
            'secondary' => $this->hexColor($this->secondary_color, self::DEFAULT_SECONDARY_COLOR),
            'accent' => $this->hexColor($this->accent_color, self::DEFAULT_ACCENT_COLOR),
        ];
    }

    private function hexColor(?string $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)
            ? strtoupper($value)
            : $fallback;
    }
}
