<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PaymentMethod extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'type', 'details', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function scopeShownOnInvoices(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function shownOnInvoicesFor(?int $businessId): Collection
    {
        $query = static::withoutGlobalScope('business')->shownOnInvoices();

        if (Schema::hasColumn('payment_methods', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->orderBy('name')->get();
    }
}
