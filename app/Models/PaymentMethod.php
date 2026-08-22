<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'type', 'details', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
