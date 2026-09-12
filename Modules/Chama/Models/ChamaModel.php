<?php

namespace Modules\Chama\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class ChamaModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}
