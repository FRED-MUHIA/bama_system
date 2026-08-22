<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'company_name', 'logo_path', 'phone', 'email', 'address', 'website',
        'tax_name', 'tax_rate', 'currency_code', 'locale', 'default_terms',
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
}
