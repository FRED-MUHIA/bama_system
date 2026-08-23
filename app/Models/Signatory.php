<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Model;

class Signatory extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'title', 'signature_path', 'stamp_path', 'is_default', 'is_active',
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

        return PublicUpload::url($this->signature_path);
    }

    public function signatureFilePath(): ?string
    {
        if (! $this->signature_path) {
            return null;
        }

        return PublicUpload::filePath($this->signature_path);
    }

    public function stampUrl(): ?string
    {
        if (! $this->stamp_path) {
            return null;
        }

        return PublicUpload::url($this->stamp_path);
    }

    public function stampFilePath(): ?string
    {
        if (! $this->stamp_path) {
            return null;
        }

        return PublicUpload::filePath($this->stamp_path);
    }
}
