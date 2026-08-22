@extends('layouts.app')
@section('title', 'Retail Settings')

@section('content')
@include('retail.partials.nav')
<div class="card p-3">
    <h1 class="h4 mb-3">Retail Settings</h1>
    <div class="row g-2">
        <div class="col-md-4"><a class="btn btn-outline-dark w-100 text-start" href="{{ route('settings.edit') }}"><i class="bi bi-credit-card me-1"></i>Payment Methods</a></div>
        <div class="col-md-4"><a class="btn btn-outline-dark w-100 text-start" href="{{ route('administration.index') }}"><i class="bi bi-shield-lock me-1"></i>Retail Roles & Permissions</a></div>
        <div class="col-md-4"><a class="btn btn-outline-dark w-100 text-start" href="{{ route('products.index') }}"><i class="bi bi-box-seam me-1"></i>Shared Product Defaults</a></div>
    </div>
    <div class="border-top mt-3 pt-3">
        <h2 class="h5 mb-2">Regional Tax & Currency Mapping</h2>
        <form method="POST" action="{{ route('retail.settings.tax-jurisdictions.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2"><input class="form-control" name="country" placeholder="Country" required></div>
            <div class="col-md-2"><input class="form-control" name="region" placeholder="Region"></div>
            <div class="col-md-2"><input class="form-control" name="tax_name" value="VAT" required></div>
            <div class="col-md-1"><input class="form-control" name="tax_code" placeholder="Code"></div>
            <div class="col-md-1"><input class="form-control" name="tax_rate" type="number" step="0.0001" placeholder="Rate" required></div>
            <div class="col-md-1"><input class="form-control" name="currency_code" value="KES" maxlength="3" required></div>
            <div class="col-md-2"><select class="form-select" name="status"><option>Active</option><option>Inactive</option></select></div>
            <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
        </form>
        <div class="table-responsive mt-3">
            <table class="table align-middle mb-0">
                <thead><tr><th>Jurisdiction</th><th>Tax</th><th>Rate</th><th>Currency</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($taxJurisdictions as $tax)
                        <tr><td>{{ $tax->country }} {{ $tax->region ? '/ '.$tax->region : '' }}</td><td>{{ $tax->tax_name }} {{ $tax->tax_code }}</td><td>{{ number_format((float) $tax->tax_rate, 4) }}%</td><td>{{ $tax->currency_code }}</td><td><span class="status-pill">{{ $tax->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No retail tax jurisdictions mapped yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
