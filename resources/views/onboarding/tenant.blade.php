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
                        <select class="form-select" id="tenant-industry-select" name="industry" required>
                            @foreach($industries as $industry)
                                <option value="{{ $industry['slug'] }}" @selected(old('industry', 'professional-services') === $industry['slug'])>{{ $industry['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sub-industry</label>
                        <select class="form-select" id="tenant-sub-industry-select" name="sub_industry" data-selected="{{ old('sub_industry') }}" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plan</label>
                        <select class="form-select" name="plan">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->slug }}" @selected(old('plan', 'starter') === $plan->slug)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="rounded border bg-light p-3" id="tenant-industry-preview" aria-live="polite"></div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-warning">Provision tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const tenantIndustries = @json($industries->values());
    const tenantIndustrySelect = document.getElementById('tenant-industry-select');
    const tenantSubIndustrySelect = document.getElementById('tenant-sub-industry-select');
    const tenantIndustryPreview = document.getElementById('tenant-industry-preview');

    function tenantEscapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character]));
    }

    function tenantCurrentIndustry() {
        return tenantIndustries.find((industry) => industry.slug === tenantIndustrySelect.value) || tenantIndustries[0];
    }

    function fillTenantSubIndustries() {
        const industry = tenantCurrentIndustry();
        const selected = tenantSubIndustrySelect.dataset.selected;
        const allowed = industry.registration_sub_industries || [];
        const subIndustries = allowed.length
            ? (industry.sub_industries || []).filter((subIndustry) => allowed.includes(subIndustry.slug))
            : (industry.sub_industries || []);

        tenantSubIndustrySelect.innerHTML = subIndustries.map((subIndustry, index) => {
            const isSelected = selected ? selected === subIndustry.slug : index === 0;

            return `<option value="${tenantEscapeHtml(subIndustry.slug)}" ${isSelected ? 'selected' : ''}>${tenantEscapeHtml(subIndustry.name)}</option>`;
        }).join('');

        tenantSubIndustrySelect.dataset.selected = tenantSubIndustrySelect.value;
        updateTenantIndustryPreview();
    }

    function updateTenantIndustryPreview() {
        const industry = tenantCurrentIndustry();
        const subIndustry = (industry.sub_industries || []).find((item) => item.slug === tenantSubIndustrySelect.value) || {};

        tenantIndustryPreview.innerHTML = `
            <strong>${tenantEscapeHtml(industry.name)}${subIndustry.name ? ' - ' + tenantEscapeHtml(subIndustry.name) : ''}</strong>
            <p class="mb-0 mt-1 text-muted">${tenantEscapeHtml(subIndustry.description || industry.description || '')}</p>
        `;
    }

    tenantIndustrySelect.addEventListener('change', () => {
        tenantSubIndustrySelect.dataset.selected = '';
        fillTenantSubIndustries();
    });
    tenantSubIndustrySelect.addEventListener('change', () => {
        tenantSubIndustrySelect.dataset.selected = tenantSubIndustrySelect.value;
        updateTenantIndustryPreview();
    });

    fillTenantSubIndustries();
</script>
@endsection
