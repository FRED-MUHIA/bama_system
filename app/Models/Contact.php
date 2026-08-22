<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'client_id', 'full_name', 'email', 'phone', 'position', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function client() { return $this->belongsTo(Client::class); }
    public function projects() { return $this->hasMany(Project::class); }
}
