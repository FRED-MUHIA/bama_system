<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['recipient_email', 'subject', 'message', 'status', 'error', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];

    public function emailable() { return $this->morphTo(); }
}
