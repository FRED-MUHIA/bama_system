@extends('layouts.app')

@section('title', 'Create tenant')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Tenant provisioning</div>
            <div class="card-body">
                <form method="post" action="{{ route('onboarding.tenant.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Organization name</label>
                        <input class="form-control" name="tenant_name" value="{{ old('tenant_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Primary business</label>
                        <input class="form-control" name="business_name" value="{{ old('business_name') }}" placeholder="Defaults to organization">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Industry</label>
                        <select class="form-select" name="industry" required>
                            @foreach($industries as $industry)
                                <option value="{{ $industry }}" @selected(old('industry', 'ProfessionalServices') === $industry)>{{ preg_replace('/(?<!^)[A-Z]/', ' $0', $industry) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plan</label>
                        <select class="form-select" name="plan">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->slug }}" @selected(old('plan', 'starter') === $plan->slug)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-warning">Provision tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
