@extends('layouts.app')
@section('title','Projects')
@section('content')
<div class="d-flex justify-content-end mb-3"><a class="btn btn-warning" href="{{ route('projects.create') }}"><i class="bi bi-plus-circle"></i> New Project</a></div>
<div class="card"><div class="card-body table-responsive"><table class="table align-middle">
<thead><tr><th>Project</th><th>Client</th><th>Site</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($projects as $project)
<tr><td><a href="{{ route('projects.show',$project) }}">{{ $project->project_name }}</a></td><td>{{ $project->client->name }}</td><td>{{ $project->site?->site_name ?: '-' }}</td><td><span class="status-pill">{{ $project->status }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('projects.edit',$project) }}"><i class="bi bi-pencil"></i></a></td></tr>
@empty <tr><td colspan="5" class="text-muted">No projects yet.</td></tr>@endforelse
</tbody></table>{{ $projects->links() }}</div></div>
@endsection
