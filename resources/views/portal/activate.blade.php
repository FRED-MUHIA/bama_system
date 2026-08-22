@extends('layouts.app')
@section('title','Activate Portal')
@section('content')
<div class="row justify-content-center"><div class="col-md-5"><div class="card"><div class="card-body"><h1 class="h5">Activate Portal Access</h1><p class="text-muted">{{ $invitation->email }}</p><form method="post" action="{{ route('portal.activate.store',$invitation->token) }}">@csrf<input class="form-control mb-2" name="name" placeholder="Your name" required><input class="form-control mb-2" name="password" type="password" placeholder="Password" required><input class="form-control mb-3" name="password_confirmation" type="password" placeholder="Confirm password" required><button class="btn btn-warning w-100">Activate</button></form></div></div></div></div>
@endsection
