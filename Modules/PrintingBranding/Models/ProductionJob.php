<?php

namespace Modules\PrintingBranding\Models;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Team;

class ProductionJob extends PrintingBrandingModel
{
    protected $table = 'printing_jobs';

    protected $casts = [
        'specifications' => 'array',
        'materials_required' => 'array',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function assignedTeam()
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function ticket()
    {
        return $this->hasOne(JobTicket::class, 'job_id');
    }

    public function artworks()
    {
        return $this->hasMany(Artwork::class, 'job_id');
    }

    public function reservations()
    {
        return $this->hasMany(MaterialReservation::class, 'job_id');
    }

    public function cost()
    {
        return $this->hasOne(JobCost::class, 'job_id');
    }
}
