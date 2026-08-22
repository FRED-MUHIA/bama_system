<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Signatory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'title', 'signature_path', 'is_default', 'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function signatureUrl(): ?string
    {
        if (! $this->signature_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->signature_path)) {
            return Storage::url($this->signature_path);
        }

        if (file_exists(public_path($this->signature_path))) {
            return asset($this->signature_path);
        }

        return null;
    }

    public function signatureFilePath(): ?string
    {
        if (! $this->signature_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->signature_path)) {
            return Storage::disk('public')->path($this->signature_path);
        }

        $publicPath = public_path($this->signature_path);

        return file_exists($publicPath) ? $publicPath : null;
    }
}
