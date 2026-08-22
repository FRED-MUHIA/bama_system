@extends('layouts.app')
@section('title','Profit')
@section('content')
<div class="card"><div class="card-body table-responsive"><table class="table align-middle">
<thead><tr><th>Project</th><th>Revenue</th><th>Expected Profit</th><th>Actual Profit</th><th>Collected Profit</th><th>Margin</th></tr></thead><tbody>
@foreach($projects as $project)
@php $revenue=$project->revenue(); $actual=$project->actualCost(); $expected=$project->expectedCost(); $collected=$project->collected(); @endphp
<tr><td><a href="{{ route('projects.show',$project) }}">{{ $project->project_name }}</a><div class="small text-muted">{{ $project->client?->name }}</div></td><td>{{ number_format($revenue,2) }}</td><td>{{ number_format($revenue-$expected,2) }}</td><td>{{ number_format($revenue-$actual,2) }}</td><td>{{ number_format($collected-$actual,2) }}</td><td>{{ $revenue > 0 ? number_format((($revenue-$actual)/$revenue)*100,2) : '0.00' }}%</td></tr>
@endforeach
</tbody></table></div></div>
@endsection
