<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterAttachment extends Model
{
    protected $fillable = ['letter_id', 'document_id', 'document_type', 'title'];

    public function letter() { return $this->belongsTo(Letter::class); }
    public function document() { return $this->belongsTo(ProjectDocument::class, 'document_id'); }
}
