<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CompanySetting;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Retail\Models\RetailExpense;

class RetailExpenseController extends Controller
{
    public function index()
    {
        $query = RetailExpense::query();

        return view('retail.expenses', [
            'expenses' => (clone $query)->with('branch', 'recordedBy')->latest('expense_date')->latest('id')->paginate(25),
            'todayTotal' => (clone $query)->whereDate('expense_date', today())->sum('amount'),
            'monthTotal' => (clone $query)->whereYear('expense_date', now()->year)->whereMonth('expense_date', now()->month)->sum('amount'),
            'allTotal' => (clone $query)->sum('amount'),
            'branches' => Branch::orderBy('name')->get(),
            'currency' => CompanySetting::where('business_id', ActiveBusiness::id())->value('currency_code') ?: 'KES',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['Cash', 'M-Pesa', 'Bank', 'Card', 'Other'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('business_id', ActiveBusiness::id())],
        ]);

        RetailExpense::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('retail.expenses.index')->with('status', 'Retail expense recorded.');
    }
}
