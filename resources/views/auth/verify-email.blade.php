@extends('layouts.marketing', ['title' => 'Verify Email'])

@section('body')
<main class="bama-page min-h-screen px-5 py-12">
    <section class="bama-card mx-auto max-w-xl p-8">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-3">
            <img src="{{ asset('images/bama-solutions-02.png') }}" alt="Bama Solutions" class="bama-logo">
        </a>
        <h1 class="mt-10 text-3xl font-black">Verify your email</h1>
        <p class="mt-3 text-black">Use the verification link sent to your inbox to secure your new workspace.</p>

        @if (session('status'))
            <div class="mt-6 rounded-lg border border-[#00A651]/40 bg-[#EAF8F0] p-4 text-sm text-[#007A3B]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-500/40 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
            @csrf
            <button class="w-full rounded-lg bg-[#00A651] px-5 py-4 font-bold text-white shadow-xl shadow-[#00A651]/20">Resend verification email</button>
        </form>
        <a href="{{ route('register.welcome') }}" class="mt-5 block text-center text-sm font-semibold text-black hover:text-[#00A651]">Back to workspace status</a>
    </section>
</main>
@endsection
