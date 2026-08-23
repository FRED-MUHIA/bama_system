@extends('layouts.marketing', ['title' => 'Verify Email'])

@section('body')
<main class="min-h-screen bg-zinc-950 px-5 py-12 text-white">
    <section class="mx-auto max-w-xl rounded-3xl border border-white/10 bg-white/[.04] p-8 shadow-2xl">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#00A651] font-black">BA</span>
            <span class="font-bold">BAMA</span>
        </a>
        <h1 class="mt-10 text-3xl font-black">Verify your email</h1>
        <p class="mt-3 text-zinc-300">Use the verification link sent to your inbox to secure your new workspace.</p>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-[#00A651]/40 bg-[#00A651]/10 p-4 text-sm text-[#BDF2D4]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
            @csrf
            <button class="w-full rounded-2xl bg-[#00A651] px-5 py-4 font-bold text-white shadow-xl shadow-[#00A651]/20">Resend verification email</button>
        </form>
        <a href="{{ route('register.welcome') }}" class="mt-5 block text-center text-sm font-semibold text-zinc-300">Back to workspace status</a>
    </section>
</main>
@endsection
