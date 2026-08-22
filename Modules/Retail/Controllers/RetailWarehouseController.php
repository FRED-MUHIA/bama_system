<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailWarehouse;
use Modules\Retail\Models\RetailWarehouseBin;
use Modules\Retail\Models\RetailWarehouseZone;
use Modules\Retail\Repositories\RetailRepository;

class RetailWarehouseController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Warehousing',
            'section' => 'warehousing',
            'records' => $retail->warehouses()->paginate(20),
            'branches' => Branch::orderBy('name')->get(),
            'warehouses' => RetailWarehouse::orderBy('name')->get(),
            'zones' => RetailWarehouseZone::with('warehouse')->latest()->limit(50)->get(),
        ]);
    }

    public function store(Request $request)
    {
        RetailWarehouse::create($request->validate([
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
            'code' => ['required', 'string', 'max:50', Rule::unique('retail_warehouses', 'code')->where('business_id', ActiveBusiness::id())],
            'name' => ['required', 'string', 'max:255'],
            'warehouse_type' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Active,Inactive'],
        ]));

        return back()->with('status', 'Warehouse saved.');
    }

    public function storeZone(Request $request)
    {
        RetailWarehouseZone::create($request->validate([
            'retail_warehouse_id' => ['required', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'zone_type' => ['nullable', 'string', 'max:100'],
        ]));

        return back()->with('status', 'Warehouse zone saved.');
    }

    public function storeBin(Request $request)
    {
        RetailWarehouseBin::create($request->validate([
            'retail_warehouse_id' => ['required', Rule::exists('retail_warehouses', 'id')->where('business_id', ActiveBusiness::id())],
            'retail_warehouse_zone_id' => ['nullable', Rule::exists('retail_warehouse_zones', 'id')->where('business_id', ActiveBusiness::id())],
            'aisle' => ['nullable', 'string', 'max:100'],
            'shelf' => ['nullable', 'string', 'max:100'],
            'bin_code' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Active,Inactive,Blocked'],
        ]));

        return back()->with('status', 'Warehouse bin saved.');
    }
}
