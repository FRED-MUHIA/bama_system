<?php

namespace Modules\NGO\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\NGO\Models\NgoActivity;
use Modules\NGO\Models\NgoBeneficiary;
use Modules\NGO\Models\NgoDonor;
use Modules\NGO\Models\NgoProgram;
use Modules\NGO\Models\NgoSector;

class NgoOperationsController extends Controller
{
    public function programs()
    {
        abort_unless(auth()->user()?->hasPermission('ngo.programs.view'), 403);
        return view('ngo.records', [
            'title' => 'Programs', 'kind' => 'programs',
            'programs' => NgoProgram::with('sector', 'donor')->latest()->paginate(20),
            'sectors' => NgoSector::where('is_active', true)->orderBy('name')->get(),
            'donors' => NgoDonor::where('status', 'Active')->orderBy('name')->get(),
            'users' => User::where('is_active', true)->whereIn('id', DB::table('business_user')->where('business_id', \App\Support\ActiveBusiness::id())->pluck('user_id'))->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function storeProgram(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('ngo.programs.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'], 'ngo_sector_id' => ['nullable', Rule::exists('ngo_sectors', 'id')->where('business_id', \App\Support\ActiveBusiness::id())],
            'donor_id' => ['nullable', Rule::exists('ngo_donors', 'id')->where('business_id', \App\Support\ActiveBusiness::id())],
            'manager_id' => ['nullable', Rule::exists('business_user', 'user_id')->where('business_id', ActiveBusiness::id())], 'funding_source' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'], 'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'], 'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'], 'status' => ['required', Rule::in(['Planning', 'Active', 'Suspended', 'Completed', 'Closed'])],
        ]);
        $data['program_number'] = 'PRG-'.strtoupper(\Illuminate\Support\Str::random(8));
        NgoProgram::create($data);
        return back()->with('status', 'Program created.');
    }

    public function sectors()
    {
        abort_unless(auth()->user()?->hasPermission('ngo.programs.view'), 403);
        return view('ngo.records', ['title' => 'Program Sectors', 'kind' => 'sectors', 'sectors' => NgoSector::orderBy('name')->paginate(30)]);
    }

    public function donors()
    {
        abort_unless(auth()->user()?->hasPermission('ngo.donors.view'), 403);

        return view('ngo.records', [
            'title' => 'Donors', 'kind' => 'donors',
            'donors' => NgoDonor::withCount('programs')->latest()->paginate(25),
        ]);
    }

    public function storeDonor(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('ngo.donors.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['Individual', 'Corporate', 'Foundation', 'Government', 'International Agency', 'Development Partner', 'NGO Partner', 'Anonymous'])],
            'country' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);
        $data['donor_number'] = 'DON-'.strtoupper(\Illuminate\Support\Str::random(10));
        NgoDonor::create($data);

        return back()->with('status', 'Donor created.');
    }

    public function storeSector(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('ngo.programs.manage'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('ngo_sectors', 'name')->where('business_id', \App\Support\ActiveBusiness::id())], 'code' => ['nullable', 'string', 'max:40']]);
        NgoSector::create($data + ['is_active' => true]);
        return back()->with('status', 'Program sector saved.');
    }

    public function activities()
    {
        abort_unless(auth()->user()?->hasPermission('ngo.activities.view'), 403);
        return view('ngo.records', [
            'title' => 'Activities', 'kind' => 'activities',
            'activities' => NgoActivity::with('program', 'project')->latest('starts_on')->paginate(20),
            'programs' => NgoProgram::whereIn('status', ['Planning', 'Active'])->orderBy('name')->get(),
            'projects' => Project::orderBy('project_name')->get(),
            'users' => User::where('is_active', true)->whereIn('id', DB::table('business_user')->where('business_id', \App\Support\ActiveBusiness::id())->pluck('user_id'))->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function storeActivity(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('ngo.activities.manage'), 403);
        $data = $request->validate([
            'program_id' => ['nullable', Rule::exists('ngo_programs', 'id')->where('business_id', \App\Support\ActiveBusiness::id())],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('business_id', \App\Support\ActiveBusiness::id())],
            'name' => ['required', 'string', 'max:255'], 'activity_type' => ['nullable', 'string', 'max:120'],
            'responsible_user_id' => ['nullable', Rule::exists('business_user', 'user_id')->where('business_id', ActiveBusiness::id())], 'location' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'budget' => ['nullable', 'numeric', 'min:0'], 'status' => ['required', Rule::in(['Planned', 'In Progress', 'Completed', 'Delayed', 'Cancelled'])],
            'description' => ['nullable', 'string'],
        ]);
        NgoActivity::create($data);
        return back()->with('status', 'Activity created.');
    }

    public function beneficiaries()
    {
        abort_unless(auth()->user()?->hasPermission('ngo.beneficiaries.view'), 403);
        return view('ngo.records', [
            'title' => 'Beneficiaries', 'kind' => 'beneficiaries',
            'beneficiaries' => NgoBeneficiary::with('programs')->latest()->paginate(25),
            'programs' => NgoProgram::whereIn('status', ['Planning', 'Active'])->orderBy('name')->get(),
            'sensitive' => auth()->user()->hasPermission('ngo.beneficiaries.sensitive.view'),
        ]);
    }

    public function storeBeneficiary(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('ngo.beneficiaries.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['Individual', 'Household', 'Community', 'Institution', 'School', 'Health Facility', 'Farmer Group', 'Youth Group', "Women's Group", 'Organization'])],
            'gender' => ['nullable', 'string', 'max:40'], 'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:80'], 'country' => ['nullable', 'string', 'max:120'],
            'county' => ['nullable', 'string', 'max:120'], 'district' => ['nullable', 'string', 'max:120'],
            'ward' => ['nullable', 'string', 'max:120'], 'community' => ['nullable', 'string', 'max:160'],
            'village' => ['nullable', 'string', 'max:160'], 'registration_status' => ['required', Rule::in(['Registered', 'Verified', 'Eligible', 'Enrolled', 'Follow-up', 'Exited', 'Graduated'])],
            'program_id' => ['nullable', Rule::exists('ngo_programs', 'id')->where('business_id', ActiveBusiness::id())],
        ]);
        $programId = $data['program_id'] ?? null;
        unset($data['program_id']);
        if (! auth()->user()->hasPermission('ngo.beneficiaries.sensitive.view')) {
            unset($data['gender'], $data['date_of_birth']);
        }
        $data['beneficiary_number'] = 'BEN-'.strtoupper(\Illuminate\Support\Str::random(10));
        $data['registered_on'] = today();
        DB::transaction(function () use ($data, $programId) {
            $beneficiary = NgoBeneficiary::create($data);
            if ($programId) {
                DB::table('ngo_program_beneficiaries')->insert([
                    'tenant_id' => \App\Support\ActiveTenant::id(),
                    'business_id' => ActiveBusiness::id(),
                    'program_id' => $programId,
                    'beneficiary_id' => $beneficiary->id,
                    'enrolled_on' => today(),
                    'status' => 'Enrolled',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
        return back()->with('status', 'Beneficiary registered.');
    }

    public function reports()
    {
        abort_unless(auth()->user()?->hasPermission('ngo.reports'), 403);
        return view('ngo.records', [
            'title' => 'NGO Reports', 'kind' => 'reports',
            'programCount' => NgoProgram::count(), 'activityCount' => NgoActivity::count(),
            'beneficiaryCount' => NgoBeneficiary::where('status', 'Active')->count(),
            'donorCount' => NgoDonor::where('status', 'Active')->count(),
            'activitiesByStatus' => NgoActivity::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->orderBy('status')->get(),
            'programsByStatus' => NgoProgram::select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->orderBy('status')->get(),
        ]);
    }
}
