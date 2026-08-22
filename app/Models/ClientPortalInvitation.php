<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ClientPortalInvitation extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'client_id', 'contact_id', 'user_id', 'email', 'token', 'status', 'invited_at', 'activated_at'];
    protected $casts = ['invited_at' => 'datetime', 'activated_at' => 'datetime'];

    public function client() { return $this->belongsTo(Client::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function user() { return $this->belongsTo(User::class); }
}
