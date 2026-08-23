@extends('layouts.app')
@section('title','Send Letter')
@section('content')
<div class="card"><div class="card-body">
    <h2 class="h5">{{ $letter->letter_number }} · {{ $letter->subject }}</h2>
    <form method="post" action="{{ route('letters.deliver',$letter) }}">
        @csrf
        <label class="form-label mt-2">Action</label>
        <select class="form-select" name="mode">
            <option value="generate">Generate only</option>
            <option value="email">Generate + email</option>
            <option value="portal">Generate + portal publish</option>
        </select>
        <label class="form-label mt-3">Recipient</label>
        <input class="form-control" name="recipient" type="email" value="{{ old('recipient',$letter->client?->email) }}">
        <label class="form-label mt-3">CC</label>
        <input class="form-control" name="cc" value="{{ old('cc') }}" placeholder="name@example.com, accounts@example.com">
        <label class="form-label mt-3">Message</label>
        <textarea class="form-control" name="message" rows="6">{{ old('message',"Please find attached {$letter->letter_number}.") }}</textarea>
        <button class="btn btn-warning mt-3"><i class="bi bi-send"></i> Continue</button>
    </form>
</div></div>
@endsection
