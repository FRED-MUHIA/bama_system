@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<div class="row justify-content-center align-items-center" style="min-height:80vh"><div class="col-md-5">
    <div class="card"><div class="card-body p-4">
        <h1 class="h4">Reset your password</h1>
        <form method="post" action="{{ route('password.email') }}">@csrf
            <label class="form-label">Email address</label><input name="email" type="email" class="form-control mb-3" required>
            <button class="btn btn-warning">Send reset link</button> <a class="btn btn-link" href="{{ route('login') }}">Back to login</a>
        </form>
    </div></div>
</div></div>
@endsection
