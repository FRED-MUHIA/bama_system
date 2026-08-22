<?php

namespace Modules\Construction\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;

class ConstructionCertificate extends ConstructionModel
{
    protected $table = 'construction_certificates';

    public function project() { return $this->belongsTo(Project::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
