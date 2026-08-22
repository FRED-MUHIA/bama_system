@extends('layouts.marketing', ['title' => 'Choose Plan'])

@section('body')
<x-registration-shell :step="$step">
    <div class="rounded-[18px] border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-200/70 sm:p-6">
        <p class="text-xs font-bold uppercase text-[#00A651]">Step 3</p>
        <h1 class="mt-2 text-3xl font-black">Choose your trial plan</h1>
        <p class="mt-2 text-sm text-black">All plans start with a 14-day free trial. You can change plans later.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.plan.store') }}" class="mt-5">
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
                <button class="flex-1 rounded-lg bg-[#00A651] px-6 py-3 text-base font-black text-white shadow-xl shadow-[#00A651]/20">Provision workspace</button>
            </div>
        </form>
    </div>
</x-registration-shell>
@endsection
