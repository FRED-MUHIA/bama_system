<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Model;

class TenantTheme extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'primary_color', 'secondary_color', 'accent_color', 'logo_path', 'favicon_path', 'dark_mode_enabled', 'custom_colors'];

    protected $casts = ['dark_mode_enabled' => 'boolean', 'custom_colors' => 'array'];

    public function logoUrl(): ?string
    {
        return PublicUpload::url($this->logo_path);
    }

    public function faviconUrl(): ?string
    {
        return PublicUpload::url($this->favicon_path);
    }
}
