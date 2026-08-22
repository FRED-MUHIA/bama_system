<?php

namespace Modules\Retail\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class RetailModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}
