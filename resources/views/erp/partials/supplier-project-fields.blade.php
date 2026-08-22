<label class="form-label mb-1">Supplier</label>
<select class="form-select mb-2" name="supplier_id" required>
    <option value="">Choose supplier</option>
    @foreach($suppliers as $supplier)
        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
    @endforeach
</select>

<label class="form-label mb-1">Project</label>
<select class="form-select mb-2" name="project_id">
    <option value="">No project</option>
    @foreach($projects as $project)
        <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->project_name }}</option>
    @endforeach
</select>

<div class="row g-2 mb-2">
    <div class="col-md-6">
        <label class="form-label mb-1">Department</label>
        <select class="form-select" name="department_id">
            <option value="">Automatic</option>
            @foreach($departments ?? collect() as $department)
                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label mb-1">Cost Center</label>
        <select class="form-select" name="cost_center_id">
            <option value="">Automatic</option>
            @foreach($costCenters ?? collect() as $center)
                <option value="{{ $center->id }}" @selected(old('cost_center_id') == $center->id)>{{ $center->department?->name }} / {{ $center->name }}</option>
            @endforeach
        </select>
    </div>
</div>
