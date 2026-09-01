@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<div class="auth-mobile-stage">
    <div class="auth-mobile-card">
        <div class="auth-mobile-brand">BAMA</div>
        <h1>Reset your password</h1>
        <p>Enter your account email and we will send a secure reset link.</p>
        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <label class="form-label">Email address</label>
            <input name="email" type="email" class="form-control mb-3" autocomplete="email" autocapitalize="none" spellcheck="false" required>
            <button class="btn btn-warning w-100">Send reset link</button>
            <a class="btn btn-link w-100 mt-2" href="{{ route('login') }}">Back to login</a>
        </form>
    </div>
</div>
@endsection
