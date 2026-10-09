@extends('layouts.app')

@section('title', 'Retail Expenses')

@section('content')
<div class="page-shell">
    <x-page-header title="Retail Expenses" kicker="Retail" subtitle="Record and review day-to-day shop expenses." />

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3 mb-4">
        @foreach(['Today' => $todayTotal, 'This month' => $monthTotal, 'All recorded' => $allTotal] as $label => $total)
            <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted">{{ $label }}</div><strong class="fs-4">{{ $currency }} {{ number_format((float) $total, 2) }}</strong></div></div></div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white"><h2 class="h5 mb-0">Add an expense</h2></div>
        <div class="card-body">
            <form method="post" action="{{ route('retail.expenses.store') }}" class="row g-3">
                @csrf
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="expense-date">Date</label><input id="expense-date" class="form-control" type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="expense-category">Category</label><input id="expense-category" class="form-control" name="category" value="{{ old('category') }}" placeholder="Rent, transport, supplies" maxlength="120" required></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="expense-amount">Amount ({{ $currency }})</label><input id="expense-amount" class="form-control" type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label" for="expense-payment">Paid using</label><select id="expense-payment" class="form-select" name="payment_method" required>@foreach(['Cash', 'M-Pesa', 'Bank', 'Card', 'Other'] as $method)<option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="expense-description">What was it for?</label><input id="expense-description" class="form-control" name="description" value="{{ old('description') }}" maxlength="1000" required></div>
                <div class="col-md-6"><label class="form-label" for="expense-vendor">Vendor / paid to</label><input id="expense-vendor" class="form-control" name="vendor" value="{{ old('vendor') }}" maxlength="255"></div>
                <div class="col-md-6"><label class="form-label" for="expense-reference">Receipt or reference number</label><input id="expense-reference" class="form-control" name="reference" value="{{ old('reference') }}" maxlength="120"></div>
                <div class="col-md-6"><label class="form-label" for="expense-branch">Store</label><select id="expense-branch" class="form-select" name="branch_id"><option value="">Current business</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-12"><button class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Record expense</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2"><h2 class="h5 mb-0">Expense history</h2><span class="text-muted small">{{ $expenses->total() }} records</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Date</th><th>Category & details</th><th>Store</th><th>Paid using</th><th>Recorded by</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="text-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td><strong>{{ $expense->category }}</strong><div>{{ $expense->description }}</div>@if($expense->vendor || $expense->reference)<small class="text-muted">{{ $expense->vendor }}@if($expense->vendor && $expense->reference) · @endif{{ $expense->reference }}</small>@endif</td>
                            <td>{{ $expense->branch?->name ?? 'Business' }}</td>
                            <td>{{ $expense->payment_method }}</td>
                            <td>{{ $expense->recordedBy?->name ?? '—' }}</td>
                            <td class="text-end text-nowrap fw-semibold">{{ $currency }} {{ number_format((float) $expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No retail expenses have been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())<div class="card-footer bg-white">{{ $expenses->links() }}</div>@endif
    </div>
</div>
@endsection
