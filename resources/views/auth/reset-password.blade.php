@extends('layouts.app')
@section('title','Reset Password')
@section('content')
<div class="row justify-content-center align-items-center" style="min-height:80vh"><div class="col-md-5">
    <div class="card"><div class="card-body p-4">
        <h1 class="h4">Choose a new password</h1>
        <form method="post" action="{{ route('password.update') }}">@csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" value="{{ $email ?? old('email') }}" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Confirm password</label><input name="password_confirmation" type="password" class="form-control" required></div>
            <button class="btn btn-warning">Reset password</button>
        </form>
    </div></div>
</div></div>
@endsection
