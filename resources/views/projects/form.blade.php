@extends('layouts.app')
@section('title', $project->exists ? 'Edit Project' : 'Create Project')
@section('content')
<div class="card"><div class="card-body">
<form method="post" action="{{ $project->exists ? route('projects.update',$project) : route('projects.store') }}">@csrf @if($project->exists) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Project name</label><input class="form-control" name="project_name" value="{{ old('project_name',$project->project_name) }}" required></div>
        <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status',$project->status)===$status)>{{ $status }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Client</label><select class="form-select" name="client_id" required><option value="">Select</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id',$project->client_id)==$client->id)>{{ $client->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Site</label><select class="form-select" name="site_id"><option value="">Optional</option>@foreach($clients as $client)@foreach($client->sites as $site)<option value="{{ $site->id }}" @selected(old('site_id',$project->site_id)==$site->id)>{{ $client->name }} - {{ $site->site_name }}</option>@endforeach @endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Contact</label><select class="form-select" name="contact_id"><option value="">Optional</option>@foreach($clients as $client)@foreach($client->contacts as $contact)<option value="{{ $contact->id }}" @selected(old('contact_id',$project->contact_id)==$contact->id)>{{ $client->name }} - {{ $contact->full_name }}</option>@endforeach @endforeach</select></div>
        <div class="col-12"><label class="form-label">Scope</label><textarea class="form-control" name="scope" rows="4">{{ old('scope',$project->scope) }}</textarea></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3">{{ old('notes',$project->notes) }}</textarea></div>
    </div>
    <div class="mt-4"><button class="btn btn-warning">Save Project</button> <a class="btn btn-link" href="{{ route('projects.index') }}">Cancel</a></div>
</form>
</div></div>
@endsection
