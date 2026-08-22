<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TenantTheme extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'primary_color', 'secondary_color', 'accent_color', 'logo_path', 'favicon_path', 'dark_mode_enabled', 'custom_colors'];

    protected $casts = ['dark_mode_enabled' => 'boolean', 'custom_colors' => 'array'];

    public function logoUrl(): ?string { return $this->logo_path ? Storage::url($this->logo_path) : null; }
    public function faviconUrl(): ?string { return $this->favicon_path ? Storage::url($this->favicon_path) : null; }
}
