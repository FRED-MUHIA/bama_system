@extends('layouts.marketing', ['title' => 'Verify Email'])

@section('body')
<x-auth-layout
    title="Verify your email"
    intro="Use the verification link sent to your inbox to secure your new workspace."
    :back-url="route('landing')"
>
        @if (session('status'))
            <div class="mb-3 rounded-xl border border-[#00A651]/40 bg-[#EAF8F0] p-3 text-sm text-[#007A3B]">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-3 rounded-xl border border-red-500/40 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="auth-primary-action w-full rounded-xl px-5 py-3 font-semibold">Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
            @csrf
            <button class="w-full text-sm font-semibold text-black hover:text-[#00A651]">Back to login page</button>
        </form>
</x-auth-layout>
@endsection
