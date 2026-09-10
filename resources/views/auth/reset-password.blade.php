@extends('layouts.app')
@section('title','Reset Password')
@section('content')
<x-auth-layout
    title="Choose a new password"
    intro="Use a strong password for your workspace account."
    :back-url="route('login')"
>
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input name="email" type="email" value="{{ $email ?? old('email') }}" class="form-control" autocomplete="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-wrap">
                    <input id="reset-password" name="password" type="password" class="form-control" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="reset-password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm password</label>
                <div class="password-wrap">
                    <input id="reset-password-confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" aria-label="Show password" data-password-toggle="reset-password-confirmation"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button class="btn btn-warning w-100">Reset password</button>
        </form>
</x-auth-layout>
@endsection
