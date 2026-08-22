<?php

namespace Shared\Communication\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

abstract class CommunicationModel extends Model
{
    use BelongsToBusiness;

    protected $guarded = [];
}
