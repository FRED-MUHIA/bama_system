<?php

namespace Modules\RealEstate\Models;

use App\Models\Project as SharedProject;

class DevelopmentProject extends RealEstateModel
{
    protected $table = 'real_estate_development_projects';
    protected $casts = ['budget' => 'decimal:2', 'actual_cost' => 'decimal:2'];

    public function sharedProject() { return $this->belongsTo(SharedProject::class, 'project_id'); }
    public function property() { return $this->belongsTo(Property::class, 'real_estate_property_id'); }
}
