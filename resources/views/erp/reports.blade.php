@extends('layouts.app')
@section('title','ERP Reports')
@section('content')
<div class="row g-3 mb-4">@foreach(['Revenue'=>$revenue,'Profit'=>$revenue-$costs,'Outstanding'=>$revenue-$collected,'Supplier'=>$supplierOutstanding,'Tax'=>$taxDue] as $label=>$value)<div class="col-md"><div class="card"><div class="card-body"><div class="text-muted">{{ $label }}</div><div class="h4 mb-0">{{ number_format($value,2) }}</div></div></div></div>@endforeach</div>
<div class="card"><div class="card-body table-responsive"><h2 class="h5">Project Report</h2><table class="table"><thead><tr><th>Project</th><th>Revenue</th><th>Collected</th><th>Costs</th><th>Profit</th></tr></thead><tbody>@foreach($projects as $project)<tr><td><a href="{{ route('projects.show',$project) }}">{{ $project->project_name }}</a></td><td>{{ number_format($project->revenue(),2) }}</td><td>{{ number_format($project->collected(),2) }}</td><td>{{ number_format($project->actualCost(),2) }}</td><td>{{ number_format($project->revenue()-$project->actualCost(),2) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
