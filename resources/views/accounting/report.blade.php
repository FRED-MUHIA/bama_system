@extends('layouts.app')
@section('title', ucwords(str_replace('-', ' ', $type)))
@section('content')
<div class="d-flex justify-content-between mb-3"><div><h2 class="h5">{{ $project->project_name }}</h2><p class="text-muted">{{ ucwords(str_replace('-', ' ', $type)) }}</p></div><a class="btn btn-outline-dark" href="{{ request()->fullUrlWithQuery(['csv'=>1]) }}">Export CSV</a></div>
<div class="card p-3"><div class="table-responsive"><table class="table">@foreach($rows as $row)<tr>@foreach((array)$row as $value)<td>{{ is_numeric($value) ? number_format((float)$value,2) : $value }}</td>@endforeach</tr>@endforeach</table></div></div>
@endsection
