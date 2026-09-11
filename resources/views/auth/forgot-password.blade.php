@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<x-auth-layout
    title="Reset your password"
    intro="Enter your account email and we will send a secure reset link."
    :back-url="route('login')"
>
    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <label class="form-label" for="forgot-email">Email address</label>
        <input id="forgot-email" name="email" type="email" class="form-control mb-3" autocomplete="email" autocapitalize="none" spellcheck="false" required autofocus>
        <button class="btn btn-warning w-100"><i class="bi bi-send me-2"></i> Send reset link</button>
        <a class="btn btn-link w-100 mt-2" href="{{ route('login') }}">Back to login</a>
    </form>
</x-auth-layout>
@endsection
