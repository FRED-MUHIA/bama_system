@extends('layouts.app')
@section('title','Document Templates')
@section('content')
<div class="row g-4"><div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">Template</h2><form method="post" action="{{ route('erp.templates.store') }}">@csrf<input class="form-control mb-2" name="name" placeholder="Name" required><select class="form-select mb-2" name="type"><option>Warranty</option><option>Proposal</option><option>Reports</option><option>Handover</option><option>Checklist</option><option>Statements</option></select><select class="form-select mb-2" name="output_format"><option>PDF</option><option>DOCX</option></select><textarea class="form-control mb-2" name="content" rows="10" required>Client: {{'{{client}}'}}
Site: {{'{{site}}'}}
Project: {{'{{project}}'}}
Balance: {{'{{balance}}'}}</textarea><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> Active</label><button class="btn btn-warning btn-sm mt-2">Save Template</button></form></div></div></div><div class="col-lg-7"><div class="card"><div class="card-body"><h2 class="h5">Templates</h2>@foreach($templates as $template)<div class="border-top py-2"><strong>{{ $template->name }}</strong><span class="float-end">{{ $template->type }} · {{ $template->output_format }}</span></div>@endforeach {{ $templates->links() }}</div></div></div></div>
@endsection
