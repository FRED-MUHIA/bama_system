<?php

namespace Modules\Agriculture\Models;

use App\Models\DocumentTemplate;

class AgricultureDocument extends AgricultureModel
{
    protected $table = 'agriculture_documents';

    public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }
    public function documentable() { return $this->morphTo(); }
    public function documentTemplate() { return $this->belongsTo(DocumentTemplate::class); }
}
