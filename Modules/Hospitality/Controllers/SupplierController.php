<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Suppliers',
            'section' => 'suppliers',
            'records' => Supplier::with('purchaseOrders', 'invoices')->latest()->paginate(30),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'kra_pin' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
        ]);

        Supplier::create($data);

        return back()->with('status', 'Supplier activated for Hospitality procurement.');
    }
}
