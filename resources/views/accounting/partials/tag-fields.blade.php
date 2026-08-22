@if(\Illuminate\Support\Facades\Schema::hasTable('departments'))
@php($tagDepartments=\App\Models\Department::where('is_active',true)->orderBy('name')->get())
@php($tagCenters=\App\Models\CostCenter::where('is_active',true)->with('department')->orderBy('name')->get())
<div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">Automatic / none</option>@foreach($tagDepartments as $item)<option value="{{ $item->id }}" @selected(old('department_id',$document->department_id??null)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Cost center</label><select class="form-select" name="cost_center_id"><option value="">Automatic / none</option>@foreach($tagCenters as $item)<option value="{{ $item->id }}" @selected(old('cost_center_id',$document->cost_center_id??null)==$item->id)>{{ $item->department?->name }} / {{ $item->name }}</option>@endforeach</select></div>
@endif
