<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'project_id', 'document_template_id', 'document_type', 'title', 'content', 'status'];

    public function project() { return $this->belongsTo(Project::class); }
    public function template() { return $this->belongsTo(DocumentTemplate::class, 'document_template_id'); }
}
