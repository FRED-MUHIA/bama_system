<?php

namespace Modules\RealEstate\Models;

use App\Models\DocumentTemplate;
use App\Models\ProjectDocument;

class RealEstateDocument extends RealEstateModel
{
    protected $table = 'real_estate_documents';

    public function documentable() { return $this->morphTo(); }
    public function documentTemplate() { return $this->belongsTo(DocumentTemplate::class); }
    public function projectDocument() { return $this->belongsTo(ProjectDocument::class); }
}
