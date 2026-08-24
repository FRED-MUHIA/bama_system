@extends('layouts.app')
@section('title','User Activity')
@section('content')
@php
$label=function($event){
    if($event==='auth:login')return 'Signed in';
    if($event==='auth:logout')return 'Signed out';
    $event=str_replace('activity:','',$event);
    $parts=explode('.',$event);$action=array_pop($parts);$subject=str_replace(['-','_'],' ',implode(' ',$parts));
    $verbs=['store'=>'Created','update'=>'Updated','destroy'=>'Deleted','approve'=>'Approved','archive'=>'Archived','convert'=>'Converted','reconcile'=>'Reconciled','close'=>'Closed','reverse'=>'Reversed','unreverse'=>'Restored','deliver'=>'Delivered','submit'=>'Submitted','merge'=>'Merged','import'=>'Imported','sync'=>'Synchronized','acknowledge'=>'Acknowledged','created'=>'Created','updated'=>'Updated','deleted'=>'Deleted','revoked'=>'Revoked','forced'=>'Forced reset for'];
    return ($verbs[$action]??ucfirst(str_replace(['-','_'],' ',$action))).($subject?' '.strtolower($subject):'');
};
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div class="d-flex align-items-center gap-3"><span class="presence-dot {{$online?'online':'offline'}}"></span><div><h1 class="h4 mb-1">{{$user->name}}</h1><div class="text-muted">{{$user->job_title?:$user->email}} · <span class="{{$online?'text-success':'text-danger'}}">{{$online?'Online now':'Offline'}}</span></div></div></div><a class="btn btn-outline-dark" href="{{route('administration.index')}}">Back to Administration</a></div>
<div class="card p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Major activity</h2><p class="text-muted mb-0">A concise operational record. No request data, IP addresses or field-level details are shown.</p></div><span class="status-pill">Latest 30</span></div><div class="activity-timeline">@forelse($timeline as $item)<div class="activity-entry"><span class="activity-marker"></span><div><strong>{{$label($item->event)}}</strong><div class="text-muted small">{{$item->when->diffForHumans()}} · {{$item->when->format('d M Y, H:i')}}</div></div></div>@empty<div class="text-muted py-4 text-center">No major activity recorded yet.</div>@endforelse</div></div>
<style>.presence-dot{width:11px;height:11px;border-radius:50%;flex:0 0 11px}.presence-dot.online{background:#22c55e;box-shadow:0 0 0 5px rgba(34,197,94,.13)}.presence-dot.offline{background:#ef4444;box-shadow:0 0 0 5px rgba(239,68,68,.1)}.activity-timeline{display:grid}.activity-entry{display:flex;gap:16px;padding:14px 0 14px 8px;border-bottom:1px solid var(--bama-line)}.activity-entry:last-child{border-bottom:0}.activity-marker{width:9px;height:9px;border-radius:50%;margin-top:5px;background:#00A651;box-shadow:0 0 0 4px rgba(0,166,81,.1)}</style>
@endsection
