@extends('layouts.marketing', ['title' => 'Welcome Dashboard'])

@section('body')
<x-registration-shell :step="5">
    @php($emailVerified = auth()->user()?->hasVerifiedEmail())
    <div class="rounded-[18px] border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-200/70 sm:p-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-[#00A651]/25 bg-[#EAF8F0] p-3 text-sm text-[#007A3B]">{{ session('status') }}</div>
        @endif
        <p class="text-xs font-bold uppercase text-[#00A651]">{{ $emailVerified ? 'Workspace ready' : 'Email verification required' }}</p>
        <h1 class="mt-2 text-3xl font-black">Welcome to {{ $tenant?->name ?? 'your workspace' }}</h1>
        <p class="mt-2 text-sm text-black">
            Your tenant, business, admin user, trial subscription, theme, enabled modules, and dashboard foundation are now initialized.
            @unless($emailVerified)
                Verify your email before opening the dashboard.
            @endunless
        </p>

        @if (! empty($industryDashboard))
            <section class="mt-4 rounded-[18px] border border-[#00A651]/20 bg-[#EAF8F0] p-4">
                <p class="text-xs font-bold uppercase text-[#007A3B]">Industry dashboard loaded</p>
                <h2 class="mt-1 text-xl font-black">
                    {{ $industryDashboard['industry'] ?? 'Industry' }}
                    @if (! empty($industryDashboard['sub_industry']))
                        - {{ $industryDashboard['sub_industry'] }}
                    @endif
                </h2>
                <p class="mt-2 text-sm leading-6 text-black">{{ $industryDashboard['summary'] ?? '' }}</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach (($industryDashboard['dashboard_features'] ?? []) as $feature)
                        <div class="rounded-lg border border-[#00A651]/15 bg-white p-3 shadow-sm">
                            <p class="font-black text-black">{{ $feature }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase text-black">Dashboard feature</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! $emailVerified)
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                Check your inbox and click the verification link to activate dashboard access.
                <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                    @csrf
                    <button class="font-bold text-amber-900 underline">Resend verification link</button>
                </form>
            </div>
        @endif

        <div class="mt-5 grid gap-2">
            @foreach ($checklist as $item)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white p-3 shadow-sm">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#00A651] text-sm font-black text-white">&#10003;</span>
                    <span class="font-semibold">{{ $item }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            @if ($emailVerified)
                <a href="{{ route('dashboard') }}" class="flex-1 rounded-lg bg-[#00A651] px-6 py-3 text-center font-black text-white shadow-xl shadow-[#00A651]/20">Open dashboard</a>
                <a href="{{ route('billing.index') }}" class="rounded-lg border border-zinc-300 bg-white px-6 py-3 text-center font-bold text-black">Pay package</a>
                <a href="{{ route('settings.edit') }}" class="rounded-lg border border-zinc-300 bg-white px-6 py-3 text-center font-bold text-black">Configure branding</a>
            @else
                <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
                    @csrf
                    <button class="w-full rounded-lg bg-[#00A651] px-6 py-3 text-center font-black text-white shadow-xl shadow-[#00A651]/20">Resend verification link</button>
                </form>
                <a href="{{ route('verification.notice') }}" class="rounded-lg border border-zinc-300 bg-white px-6 py-3 text-center font-bold text-black">Verification instructions</a>
            @endif
        </div>
    </div>
</x-registration-shell>
@endsection
