<?php

namespace Modules\Retail\Services;

use App\Models\Branch;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetailShopContext
{
    public function assignedBranchId(): ?int
    {
        $id = DB::table('business_user')->where('business_id', ActiveBusiness::id())
            ->where('user_id', auth()->id())->value('branch_id');

        return $id ? (int) $id : null;
    }

    public function branches()
    {
        return Branch::where('business_id', ActiveBusiness::id())->where('is_active', true)
            ->when($this->assignedBranchId(), fn ($query, $id) => $query->whereKey($id));
    }

    public function saleContext(array $data): array
    {
        abort_unless(auth()->check(), 403);
        $assigned = $this->assignedBranchId();
        $requested = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        if ($assigned && $requested && $assigned !== $requested) {
            throw ValidationException::withMessages(['branch_id' => 'You can only sell from your assigned shop.']);
        }
        $id = $assigned ?: $requested;
        if (! $id) {
            $branches = $this->branches()->limit(2)->get();
            if ($branches->count() === 1) {
                $id = $branches->first()->id;
            } elseif ($branches->isEmpty() && ! Branch::where('business_id', ActiveBusiness::id())->exists()) {
                $id = Branch::create(['name' => 'Main Shop', 'code' => 'MAIN', 'is_active' => true])->id;
            }
        }
        if (! $id || ! $this->branches()->whereKey($id)->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'Select an active shop for this sale.']);
        }

        return array_replace($data, ['branch_id' => $id, 'cashier_id' => auth()->id()]);
    }
}
