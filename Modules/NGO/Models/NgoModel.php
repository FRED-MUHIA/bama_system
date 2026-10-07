<?php

namespace Modules\NGO\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class NgoModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}
