@extends('layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Create ').$definition['label'])
@section('content')
<div class="card border-0 shadow-sm mx-auto" style="max-width:850px">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <h1 class="h4 mb-0">{{ $record->exists ? 'Edit' : 'Create' }} {{ strtolower($definition['label']) }}</h1>
            <a href="{{ route('salon.'.$definition['index'].'.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
        @if($errors->any())<div class="alert alert-danger" role="alert">Please correct the fields below. {{ $errors->first() }}</div>@endif
        <form method="post" action="{{ $record->exists ? route('salon.records.update', [$type, $record->id]) : route('salon.records.store', $type) }}">
            @csrf
            @if($record->exists) @method('PUT') @endif
            <div class="row g-3">
                @foreach($fields as $name => $field)
                    @php
                        $value = $record->{$name};
                        if ($type === 'clients' && in_array($name, ['name', 'phone', 'email'])) $value = $record->client?->{$name};
                        if ($value instanceof \DateTimeInterface) $value = $value->format($field['type'] === 'datetime-local' ? 'Y-m-d\TH:i' : 'Y-m-d');
                        if ($field['type'] === 'time' && $value) $value = substr($value, 0, 5);
                        if (($field['lines'] ?? false) && is_array($value)) $value = implode("\n", $value);
                        if (is_bool($value)) $value = (int) $value;
                        $value = old($name, $value);
                        $required = in_array('required', $field['rules'], true);
                    @endphp
                    <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                        <label class="form-label" for="salon-{{ $name }}">{{ $field['label'] }}@if($required) <span aria-hidden="true">*</span>@endif</label>
                        @if(in_array($field['type'], ['select', 'relation', 'multiple']))
                            <select id="salon-{{ $name }}" name="{{ $name }}{{ $field['type'] === 'multiple' ? '[]' : '' }}" class="form-select @error($name) is-invalid @enderror" @required($required) @if($field['type'] === 'multiple') multiple size="6" @endif>
                                @if($field['type'] === 'relation')<option value="">Choose {{ strtolower($field['label']) }}</option>@endif
                                @foreach($field['options'] as $key => $label)
                                    <option value="{{ $key }}" @selected($field['type'] === 'multiple' ? in_array($key, (array) $value) : (string) $value === (string) $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        @elseif($field['type'] === 'textarea')
                            <textarea id="salon-{{ $name }}" name="{{ $name }}" class="form-control @error($name) is-invalid @enderror" rows="4" @required($required)>{{ $value }}</textarea>
                        @else
                            <input id="salon-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] }}" value="{{ $value }}" class="form-control @error($name) is-invalid @enderror" @required($required) @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['max'])) max="{{ $field['max'] }}" @endif @if(isset($field['step'])) step="{{ $field['step'] }}" @endif>
                        @endif
                        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-success" type="submit">{{ $record->exists ? 'Save changes' : 'Create '.strtolower($definition['label']) }}</button>
                <a class="btn btn-outline-secondary" href="{{ route('salon.'.$definition['index'].'.index') }}">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
