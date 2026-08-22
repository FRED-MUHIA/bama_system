<?php

namespace Modules\Salon\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class SalonModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}
