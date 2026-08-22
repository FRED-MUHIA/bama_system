<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterVersion extends Model
{
    protected $fillable = ['letter_id', 'version', 'subject', 'content', 'status', 'created_by'];

    public function letter() { return $this->belongsTo(Letter::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
