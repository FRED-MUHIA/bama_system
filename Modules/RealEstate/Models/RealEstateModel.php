<?php

namespace Modules\RealEstate\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class RealEstateModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}
