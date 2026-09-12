<?php

namespace Modules\Chama\Models;

class ChamaDocument extends ChamaModel
{
    protected $table = 'chama_documents';

    public function member() { return $this->belongsTo(Member::class); }
    public function documentable() { return $this->morphTo(); }
}
