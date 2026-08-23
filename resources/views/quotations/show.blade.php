@extends('layouts.app')
@section('title',$quotation->quotation_number)
@section('content')
<div class="d-flex gap-2 flex-wrap justify-content-end mb-3">
    <a class="btn btn-outline-dark" href="{{ route('quotations.edit',$quotation) }}"><i class="bi bi-pencil"></i> Edit</a>
    <a class="btn btn-outline-warning" href="{{ route('quotations.download',$quotation) }}"><i class="bi bi-download"></i> PDF</a>
    <a class="btn btn-outline-primary" href="{{ route('quotations.email',$quotation) }}"><i class="bi bi-envelope"></i> Email</a>
    @unless($quotation->invoice)<form method="post" action="{{ route('quotations.convert',$quotation) }}">@csrf<button class="btn btn-warning"><i class="bi bi-arrow-right-circle"></i> Convert</button></form>@endunless
</div>
@include('documents.document-sheet', ['type' => 'Quotation', 'document' => $quotation])
@endsection
