@extends('layouts.app')
@section('title', $quotation->exists ? 'Edit Quotation' : 'Create Quotation')
@section('content')
@php $items = old('items', $quotation->items?->toArray() ?: [['title'=>'','description'=>'','quantity'=>1,'unit_price'=>0,'discount'=>0,'tax_rate'=>'']]); @endphp
<form method="post" action="{{ $quotation->exists ? route('quotations.update',$quotation) : route('quotations.store') }}">@csrf @if($quotation->exists) @method('PUT') @endif
<div class="card mb-3"><div class="card-body"><div class="row g-3">
    @include('documents.client-fields', ['document' => $quotation])
    @include('documents.project-fields', ['document' => $quotation])
    <div class="col-md-3"><label class="form-label">Date</label><input class="form-control" type="date" name="quotation_date" value="{{ old('quotation_date', optional($quotation->quotation_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Valid until</label><input class="form-control" type="date" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until)->format('Y-m-d')) }}"></div>
    <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><option>draft</option><option @selected(old('status',$quotation->status)==='sent')>sent</option><option @selected(old('status',$quotation->status)==='accepted')>accepted</option></select></div>
</div></div></div>
@include('documents.items-table', ['items' => $items])
<div class="card mt-3"><div class="card-body"><label class="form-label">Terms</label><textarea class="form-control mb-3" name="terms">{{ old('terms',$quotation->terms ?? $settings?->default_terms) }}</textarea><label class="form-label">Notes</label><textarea class="form-control" name="notes">{{ old('notes',$quotation->notes) }}</textarea></div></div>
<div class="mt-3"><button class="btn btn-warning">Save Quotation</button></div>
</form>
@endsection
