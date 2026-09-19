@php
    $brand = (array) data_get(\App\Models\MarketingPage::resolve('home')->sections ?? [], 'brand', []);
    $configuredLogoPath = data_get($brand, 'logo_path');
    $trialLogoUrl = $configuredLogoPath && $configuredLogoPath !== 'logos/llOAKRuYpeIgIZUIUYxVLE0Nj86xZeKTcalHp7ZC.png'
        ? \App\Support\PublicUpload::url($configuredLogoPath)
        : null;
@endphp

@extends('layouts.marketing', ['title' => 'Choose Plan'])

@section('body')
<x-registration-shell :step="$step">
    <div class="registration-card rounded-[18px] border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-200/70 sm:p-6">
        <p class="text-xs font-bold uppercase text-[#00A651]">Step 3</p>
        <h1 class="mt-2 text-3xl font-black">Choose your trial plan</h1>
        <p class="mt-2 text-sm text-black">All plans start with a 14-day free trial. You can change plans later.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form id="registration-plan-form" method="POST" action="{{ route('register.plan.store') }}" class="mt-5">
            @csrf
            <div class="grid gap-3 lg:grid-cols-2">
                @foreach ($plans as $plan)
                    <label class="relative block cursor-pointer rounded-[16px] border {{ ! empty($plan['highlight']) ? 'border-[#00A651] bg-[#EAF8F0]' : 'border-zinc-200 bg-white' }} p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#00A651] hover:shadow-lg">
                        <input type="radio" name="plan" value="{{ $plan['slug'] }}" class="peer sr-only" @checked(old('plan', $selectedPlan) === $plan['slug'])>
                        <span class="absolute right-4 top-4 h-5 w-5 rounded-lg border border-zinc-300 peer-checked:border-[#00A651] peer-checked:bg-[#00A651]"></span>
                        @if (! empty($plan['highlight']))
                            <span class="rounded-lg bg-[#00A651] px-3 py-1 text-xs font-black uppercase text-white">Recommended</span>
                        @endif
                        <h2 class="mt-3 text-xl font-black">{{ $plan['name'] }}</h2>
                        <p class="mt-2 min-h-10 text-sm leading-5 text-black">{{ $plan['tagline'] ?? 'Flexible plan for modern teams.' }}</p>
                        <p class="mt-4 text-2xl font-black">{{ ($plan['monthly_price'] ?? 0) > 0 ? number_format($plan['monthly_price']) : 'Custom' }} <span class="text-sm font-semibold text-black">{{ ($plan['monthly_price'] ?? 0) > 0 ? ($plan['currency'].' / mo') : '' }}</span></p>
                        <p class="mt-1 text-sm text-black">Annual: {{ ($plan['annual_price'] ?? 0) > 0 ? $plan['currency'].' '.number_format($plan['annual_price']) : 'Talk to sales' }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm text-black">
                            <span>Users: {{ $plan['limits']['users'] ?? 'Custom' }}</span>
                            <span>Storage: {{ $plan['limits']['storage'] ?? 'Custom' }}</span>
                            <span>Branches: {{ $plan['limits']['branches'] ?? 'Custom' }}</span>
                            <span>Projects: {{ $plan['limits']['projects'] ?? 'Custom' }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register.company') }}" class="rounded-lg border border-zinc-300 bg-white px-6 py-3 text-center font-bold text-black">Back</a>
                <button type="submit" class="flex-1 rounded-lg bg-[#00A651] px-6 py-3 text-base font-black text-white shadow-xl shadow-[#00A651]/20">Provision workspace</button>
            </div>
        </form>
    </div>

    <div id="trial-plan-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-[#111827]/40 px-4 backdrop-blur-[2px]">
        <div class="w-full max-w-md rounded-[28px] border border-[#D4F3E0] bg-white p-5 shadow-[0_25px_70px_rgba(0,0,0,0.18)] sm:p-6">
            <div class="flex items-center justify-end">
                <button type="button" id="close-trial-modal" aria-label="Close free trial dialog" class="flex h-9 w-9 items-center justify-center rounded-full border border-zinc-200 bg-white text-xl font-bold text-zinc-500 transition hover:border-[#00A651] hover:text-[#00A651]">×</button>
            </div>

            <div class="flex justify-center">
                <div class="grid h-20 w-20 place-items-center rounded-full bg-[#00A651] shadow-[0_10px_25px_rgba(0,166,81,0.3)]">
                    @if ($trialLogoUrl)
                        <img src="{{ $trialLogoUrl }}" alt="Brand logo" class="h-10 w-10 object-contain rounded-full bg-white p-1">
                    @else
                        <span class="text-3xl font-black text-white">✓</span>
                    @endif
                </div>
            </div>

            <h2 class="mt-5 text-center text-2xl font-black text-black">14-day free trial</h2>
            <p class="mt-3 text-center text-sm leading-6 text-zinc-700">Choose any package to continue with the free trial. You can upgrade or switch plans after your trial ends.</p>

            <div class="mt-5 space-y-3">
                @foreach ($plans as $plan)
                    <button type="button" data-plan-option="{{ $plan['slug'] }}" class="trial-plan-option flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition {{ ! empty($plan['highlight']) ? 'border-[#00A651] bg-[#EAF8F0]' : 'border-zinc-200 bg-white hover:border-[#00A651] hover:bg-[#F4FBF7]' }}">
                        <div>
                            <div class="text-base font-black text-black">{{ $plan['name'] }}</div>
                            <div class="text-xs text-zinc-600">{{ ($plan['monthly_price'] ?? 0) > 0 ? $plan['currency'].' '.number_format((float) $plan['monthly_price']) : 'Custom pricing' }} / month</div>
                        </div>
                        <span class="rounded-full bg-[#00A651] px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white">Trial</span>
                    </button>
                @endforeach
            </div>

            <button type="button" id="trial-continue-btn" class="mt-6 w-full rounded-xl bg-[#00A651] px-5 py-3 text-base font-black text-white shadow-xl shadow-[#00A651]/25 transition hover:translate-y-[-1px]">Continue with selected plan</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('trial-plan-modal');
            const closeButton = document.getElementById('close-trial-modal');
            const continueButton = document.getElementById('trial-continue-btn');
            const form = document.getElementById('registration-plan-form');
            const planOptions = [...document.querySelectorAll('[data-plan-option]')];
            const radioInputs = [...document.querySelectorAll('input[name="plan"]')];

            const setSelectedPlan = function (slug) {
                radioInputs.forEach((input) => {
                    input.checked = (input.value === slug);
                });

                planOptions.forEach((option) => {
                    const isSelected = option.dataset.planOption === slug;
                    option.classList.toggle('border-[#00A651]', isSelected);
                    option.classList.toggle('bg-[#EAF8F0]', isSelected);
                    option.classList.toggle('shadow-sm', isSelected);
                    option.classList.toggle('ring-1', isSelected);
                    option.classList.toggle('ring-[#00A651]/35', isSelected);
                });
            };

            const hideModalOnce = function () {
                modal.classList.add('hidden');
                sessionStorage.setItem('registrationTrialModalDismissed', '1');
            };

            const dismissModal = function () {
                hideModalOnce();
            };

            const submitSelectedPlan = function () {
                if (! form) {
                    return;
                }

                const checkedPlan = radioInputs.find((input) => input.checked)?.value;
                if (! checkedPlan) {
                    return;
                }

                hideModalOnce();
                form.requestSubmit();
            };

            const shouldShowModal = sessionStorage.getItem('registrationTrialModalDismissed') !== '1';
            if (! shouldShowModal) {
                modal.classList.add('hidden');
            }

            const selected = radioInputs.find((input) => input.checked)?.value || 'professional';
            setSelectedPlan(selected);

            planOptions.forEach((option) => {
                option.addEventListener('click', function () {
                    setSelectedPlan(option.dataset.planOption);
                    submitSelectedPlan();
                });
            });

            closeButton.addEventListener('click', function () {
                dismissModal();
            });

            continueButton.addEventListener('click', function () {
                submitSelectedPlan();
            });
        });
    </script>
</x-registration-shell>
@endsection
