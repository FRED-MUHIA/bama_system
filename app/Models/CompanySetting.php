<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->logo_path)) {
            return Storage::url($this->logo_path);
        }

        if (file_exists(public_path($this->logo_path))) {
            return asset($this->logo_path);
        }

        return null;
    }

    public function logoFilePath(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->path($this->logo_path);
        }

        $publicPath = public_path($this->logo_path);

        return file_exists($publicPath) ? $publicPath : null;
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
