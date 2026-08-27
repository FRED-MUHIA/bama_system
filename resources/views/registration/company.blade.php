@extends('layouts.marketing', ['title' => 'Company Setup'])

@section('body')
<x-registration-shell :step="$step">
    <div class="rounded-[18px] border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-200/70 sm:p-6">
        <p class="text-xs font-bold uppercase text-[#00A651]">Step 2</p>
        <h1 class="mt-2 text-3xl font-black">Business Information</h1>
        <p class="mt-2 text-sm text-black">Choose the industry template and operating defaults for your workspace.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.company.store') }}" class="mt-5 grid gap-4">
            @csrf
            <label class="grid gap-2">
                <span class="text-xs font-bold uppercase text-black">Company Name</span>
                <input name="company_name" value="{{ old('company_name', $company['company_name'] ?? '') }}" required class="field-control rounded-lg px-4 py-3 outline-none transition">
            </label>
            <div class="grid gap-4 lg:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Industry</span>
                    <select id="industry-select" name="industry" required class="field-control rounded-lg px-4 py-3 outline-none transition">
                        @foreach ($industries as $industry)
                            <option value="{{ $industry['slug'] }}" @selected(old('industry', $company['industry'] ?? 'professional-services') === $industry['slug'])>{{ $industry['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Sub-Industry</span>
                    <select id="sub-industry-select" name="sub_industry" data-selected="{{ old('sub_industry', $company['sub_industry'] ?? '') }}" required class="field-control rounded-lg px-4 py-3 outline-none transition"></select>
                </label>
            </div>
            <section id="industry-preview-panel" class="industry-preview-panel is-collapsed rounded-[18px] border border-[#00A651]/20 bg-[#EAF8F0] p-4" aria-live="polite">
                <style>
                    .registration-page .industry-preview-panel.is-collapsed .industry-preview-body {
                        display: none;
                    }

                    .registration-page .industry-preview-toggle i {
                        transition: transform .2s ease;
                    }

                    .registration-page .industry-preview-panel:not(.is-collapsed) .industry-preview-toggle i {
                        transform: rotate(180deg);
                    }
                </style>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase text-[#007A3B]">Industry dashboard</p>
                        <h2 id="industry-preview-title" class="mt-1 text-xl font-black text-black">Dashboard profile</h2>
                    </div>
                    <button type="button" class="industry-preview-toggle inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[#00A651]/20 bg-white text-[#007A3B] shadow-sm" aria-controls="industry-preview-body" aria-expanded="false" aria-label="Expand industry dashboard preview">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <div id="industry-preview-body" class="industry-preview-body mt-3">
                    <p id="industry-preview-copy" class="max-w-2xl text-sm leading-6 text-black"></p>
                    <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div id="industry-preview-modules" class="flex max-w-xl flex-wrap gap-2"></div>
                    </div>
                    <div id="industry-dashboard-features" class="mt-3 grid gap-2 sm:grid-cols-2"></div>
                </div>
            </section>
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Country</span>
                    <input name="country" value="{{ old('country', $company['country'] ?? 'Kenya') }}" required class="field-control rounded-lg px-4 py-3 outline-none transition">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Currency</span>
                    <input name="currency" value="{{ old('currency', $company['currency'] ?? 'KES') }}" maxlength="3" required class="field-control rounded-lg px-4 py-3 uppercase outline-none transition">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Timezone</span>
                    <select name="timezone" required class="field-control rounded-lg px-4 py-3 outline-none transition">
                        @foreach (['Africa/Nairobi', 'UTC', 'Africa/Lagos', 'Africa/Johannesburg', 'Europe/London', 'America/New_York'] as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $company['timezone'] ?? 'Africa/Nairobi') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register.account') }}" class="rounded-lg border border-zinc-300 bg-white px-6 py-3 text-center font-bold text-black">Back</a>
                <button class="flex-1 rounded-lg bg-[#00A651] px-6 py-3 text-base font-black text-white shadow-xl shadow-[#00A651]/20">Continue to plan selection</button>
            </div>
        </form>
    </div>
</x-registration-shell>

<script>
    const industries = @json($industries->values());
    const industrySelect = document.getElementById('industry-select');
    const subIndustrySelect = document.getElementById('sub-industry-select');
    const previewTitle = document.getElementById('industry-preview-title');
    const previewCopy = document.getElementById('industry-preview-copy');
    const previewModules = document.getElementById('industry-preview-modules');
    const featureGrid = document.getElementById('industry-dashboard-features');
    const previewPanel = document.getElementById('industry-preview-panel');
    const previewToggle = document.querySelector('.industry-preview-toggle');

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character]));
    }

    function currentIndustry() {
        return industries.find((industry) => industry.slug === industrySelect.value) || industries[0];
    }

    function fillSubIndustries() {
        const industry = currentIndustry();
        const selected = subIndustrySelect.dataset.selected;
        const allowed = industry.registration_sub_industries || [];
        const subIndustries = allowed.length
            ? (industry.sub_industries || []).filter((subIndustry) => allowed.includes(subIndustry.slug))
            : (industry.sub_industries || []);
        subIndustrySelect.innerHTML = subIndustries.map((subIndustry, index) => {
            const isSelected = selected ? selected === subIndustry.slug : index === 0;
            return `<option value="${escapeHtml(subIndustry.slug)}" ${isSelected ? 'selected' : ''}>${escapeHtml(subIndustry.name)}</option>`;
        }).join('');
        subIndustrySelect.dataset.selected = subIndustrySelect.value;
        updatePreview();
    }

    function updatePreview() {
        const industry = currentIndustry();
        const subIndustry = (industry.sub_industries || []).find((item) => item.slug === subIndustrySelect.value) || (industry.sub_industries || [])[0] || {};
        const features = subIndustry.dashboard_features || industry.dashboard_features || [];
        previewTitle.textContent = `${industry.name}${subIndustry.name ? ' - ' + subIndustry.name : ''}`;
        previewCopy.textContent = subIndustry.description || industry.description || '';
        previewModules.innerHTML = (industry.modules || []).map((module) => `<span class="rounded-lg bg-white px-3 py-1 text-xs font-bold text-[#007A3B] shadow-sm">${escapeHtml(module)}</span>`).join('');
        featureGrid.innerHTML = features.map((feature) => `<div class="rounded-lg border border-[#00A651]/15 bg-white p-3 shadow-sm"><p class="font-black text-black">${escapeHtml(feature)}</p><p class="mt-1 text-xs font-semibold uppercase text-black">Dashboard feature</p></div>`).join('');
    }

    function setPreviewExpanded(expanded) {
        previewPanel.classList.toggle('is-collapsed', ! expanded);
        previewToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        previewToggle.setAttribute('aria-label', expanded ? 'Minimize industry dashboard preview' : 'Expand industry dashboard preview');
    }

    industrySelect.addEventListener('change', () => {
        subIndustrySelect.dataset.selected = '';
        fillSubIndustries();
    });
    subIndustrySelect.addEventListener('change', () => {
        subIndustrySelect.dataset.selected = subIndustrySelect.value;
        updatePreview();
    });
    previewToggle.addEventListener('click', () => {
        setPreviewExpanded(previewToggle.getAttribute('aria-expanded') !== 'true');
    });
    fillSubIndustries();
    setPreviewExpanded(false);
</script>
@endsection
