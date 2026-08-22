@extends('layouts.app')
@section('title', 'Scan Devices')

@section('content')
@include('retail.partials.nav')
<div class="card p-3 mb-3">
    <form method="POST" action="{{ route('retail.scanning.devices.store') }}" class="row g-2">
        @csrf
        <div class="col-md-2"><input class="form-control" name="device_code" placeholder="Device code" required></div>
        <div class="col-md-3"><input class="form-control" name="name" placeholder="Device name" required></div>
        <div class="col-md-3"><select class="form-select" name="device_type"><option>POS Scanner</option><option>Mobile Camera</option><option>Self Checkout Camera</option><option>POS Camera</option><option>Scanner Device</option></select></div>
        <div class="col-md-2"><input class="form-control" name="register_number" placeholder="Register"></div>
        <div class="col-md-1"><select class="form-select" name="status"><option>Active</option><option>Inactive</option><option>Maintenance</option></select></div>
        <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-save"></i></button></div>
    </form>
</div>
<div class="card p-0">
    <table class="table mb-0"><thead><tr><th>Device</th><th>Type</th><th>Register</th><th>Status</th></tr></thead><tbody>
        @forelse($devices as $device)
            <tr><td>{{ $device->name }}<div class="small text-muted">{{ $device->device_code }}</div></td><td>{{ $device->device_type }}</td><td>{{ $device->register_number }}</td><td><span class="status-pill">{{ $device->status }}</span></td></tr>
        @empty
            <tr><td colspan="4" class="text-muted p-4">No scan devices yet.</td></tr>
        @endforelse
    </tbody></table>
    <div class="p-3">{{ $devices->links() }}</div>
</div>
@endsection
