<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'description'];

    public function products() { return $this->hasMany(Product::class); }
}
