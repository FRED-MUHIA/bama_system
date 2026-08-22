@if($projectLinksEnabled ?? false)
@php
    $selectedSite = old('site_id', $document->site_id);
    $selectedProject = old('project_id', $document->project_id);
    $selectedContact = old('contact_id', $document->contact_id);
@endphp
<div class="col-md-4"><label class="form-label">Site</label><select class="form-select" name="site_id"><option value="">Optional</option>@foreach($clients as $client)@foreach($client->sites as $site)<option value="{{ $site->id }}" @selected($selectedSite == $site->id)>{{ $client->name }} - {{ $site->site_name }}</option>@endforeach @endforeach</select></div>
<div class="col-md-4"><label class="form-label">Project</label><select class="form-select" name="project_id"><option value="">Optional</option>@foreach($clients as $client)@foreach($client->projects as $project)<option value="{{ $project->id }}" @selected($selectedProject == $project->id)>{{ $client->name }} - {{ $project->project_name }}</option>@endforeach @endforeach</select></div>
<div class="col-md-4"><label class="form-label">Contact</label><select class="form-select" name="contact_id"><option value="">Optional</option>@foreach($clients as $client)@foreach($client->contacts as $contact)<option value="{{ $contact->id }}" @selected($selectedContact == $contact->id)>{{ $client->name }} - {{ $contact->full_name }}</option>@endforeach @endforeach</select></div>
@endif
