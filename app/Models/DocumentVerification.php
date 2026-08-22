<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class DocumentVerification extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'document_type', 'document_id', 'document_number',
        'hash', 'public_token', 'verified_at', 'verified_by_ip',
    ];

    protected $casts = ['verified_at' => 'datetime'];

    public static function generateHash(array $data): string
    {
        return hash('sha256', json_encode($data) . now()->timestamp . \Illuminate\Support\Str::random(16));
    }

    public static function generateToken(): string
    {
        return \Illuminate\Support\Str::random(32);
    }
}
