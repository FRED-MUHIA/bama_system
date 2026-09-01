@extends('layouts.marketing', ['title' => 'Start Free Trial'])

@section('body')
<x-registration-shell :step="$step">
    <div class="rounded-[18px] border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-200/70 sm:p-6">
        <p class="text-xs font-bold uppercase text-[#00A651]">Step 1</p>
        <h1 class="mt-2 text-3xl font-black">Create Your BAMA Account</h1>
        <p class="mt-2 text-sm text-black">This account becomes the workspace owner for your tenant.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.account.store') }}" class="mt-5 grid gap-4">
            @csrf
            <label class="grid gap-2">
                <span class="text-xs font-bold uppercase text-black">Full Name</span>
                <input name="name" value="{{ old('name', $account['name'] ?? '') }}" autocomplete="name" required class="field-control rounded-lg px-4 py-3 outline-none transition">
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Email</span>
                    <input type="email" name="email" value="{{ old('email', $account['email'] ?? '') }}" autocomplete="email" autocapitalize="none" spellcheck="false" required class="field-control rounded-lg px-4 py-3 outline-none transition">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Phone Number</span>
                    <input name="phone" value="{{ old('phone', $account['phone'] ?? '') }}" autocomplete="tel" inputmode="tel" class="field-control rounded-lg px-4 py-3 outline-none transition">
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Password</span>
                    <span class="relative">
                        <input id="register-password" type="password" name="password" autocomplete="new-password" required class="field-control w-full rounded-lg px-4 py-3 pr-12 outline-none transition">
                        <button type="button" class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-zinc-500" aria-label="Show password" data-password-toggle="register-password"><i class="bi bi-eye"></i></button>
                    </span>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-bold uppercase text-black">Confirm Password</span>
                    <span class="relative">
                        <input id="register-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="field-control w-full rounded-lg px-4 py-3 pr-12 outline-none transition">
                        <button type="button" class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-zinc-500" aria-label="Show password" data-password-toggle="register-password-confirmation"><i class="bi bi-eye"></i></button>
                    </span>
                </label>
            </div>
            <button class="mt-1 rounded-lg bg-[#00A651] px-6 py-3 text-base font-black text-white shadow-xl shadow-[#00A651]/20">Continue to company setup</button>
            <p class="text-center text-sm text-black">Already have an account? <a href="{{ route('login') }}" class="font-bold text-[#00A651]">Sign in</a></p>
        </form>
    </div>
</x-registration-shell>
@endsection
