<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'client_id', 'project_id', 'site_id', 'manufacturer', 'serial_number', 'starts_at', 'expires_at', 'status', 'notes'];
    protected $casts = ['starts_at' => 'date', 'expires_at' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function claims() { return $this->hasMany(WarrantyClaim::class); }
    public function letters() { return $this->hasMany(Letter::class); }
}
