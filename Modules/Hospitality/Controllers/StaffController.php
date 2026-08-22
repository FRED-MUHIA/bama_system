<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use App\Models\IamRole;
use App\Models\User;
use App\Services\IamService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public const TITLES = [
        'General Manager',
        'Hotel Manager',
        'Front Desk Officer',
        'Reservations Officer',
        'Housekeeping Supervisor',
        'Housekeeping Staff',
        'Maintenance Officer',
        'Restaurant Manager',
        'Waiter',
        'Chef',
        'Kitchen Staff',
        'Events Coordinator',
        'Finance Officer',
    ];

    public function index()
    {
        if (Schema::hasTable('iam_roles') && Schema::hasTable('iam_permissions') && Schema::hasTable('business_user')) {
            app(IamService::class)->bootstrap();
        }

        return view('hospitality.index', [
            'title' => 'Staff',
            'section' => 'staff',
            'records' => User::where('role', '!=', 'client_portal')->orderBy('name')->paginate(30),
            'roles' => Schema::hasTable('iam_roles') ? IamRole::where('business_id', ActiveBusiness::id())->orderBy('name')->get() : collect(),
            'memberships' => Schema::hasTable('business_user') ? DB::table('business_user')->where('business_id', ActiveBusiness::id())->get()->keyBy('user_id') : collect(),
            'titles' => self::TITLES,
            'statuses' => ['Active', 'Pending Invitation', 'Inactive', 'Suspended'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'employee_number' => ['nullable', 'string', 'max:100', 'unique:users,employee_number'],
            'job_title' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'iam_role_id' => ['required', Rule::exists('iam_roles', 'id')->where('business_id', ActiveBusiness::id())],
            'status' => ['required', Rule::in(['Active', 'Pending Invitation', 'Inactive', 'Suspended'])],
            'notes' => ['nullable', 'string'],
        ]);

        $status = $data['status'];
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => ($data['username'] ?? null) ?: $this->username($data['name']),
            'employee_number' => $data['employee_number'] ?? null,
            'job_title' => $data['job_title'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'role' => 'staff',
            'status' => $status,
            'is_active' => $status === 'Active',
            'enable_password_login' => true,
            'enable_otp_login' => true,
            'enable_magic_link_login' => true,
            'password' => Hash::make(Str::random(64)),
            'date_joined' => now()->toDateString(),
        ]);

        DB::table('business_user')->updateOrInsert(
            ['business_id' => ActiveBusiness::id(), 'user_id' => $user->id],
            [
                'iam_role_id' => $data['iam_role_id'],
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('status', 'Staff member '.$user->name.' added with title '.$user->job_title.'.');
    }

    public function update(Request $request, User $staff)
    {
        $data = $request->validate([
            'job_title' => ['required', 'string', 'max:255'],
            'iam_role_id' => ['required', Rule::exists('iam_roles', 'id')->where('business_id', ActiveBusiness::id())],
            'status' => ['required', Rule::in(['Active', 'Pending Invitation', 'Inactive', 'Suspended'])],
        ]);

        $staff->update([
            'job_title' => $data['job_title'],
            'status' => $data['status'],
            'is_active' => $data['status'] === 'Active',
        ]);

        DB::table('business_user')->updateOrInsert(
            ['business_id' => ActiveBusiness::id(), 'user_id' => $staff->id],
            [
                'iam_role_id' => $data['iam_role_id'],
                'status' => $data['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('status', 'Staff role updated.');
    }

    private function username(string $name): string
    {
        $base = Str::slug($name, '.') ?: 'staff';
        $username = $base;
        $counter = 2;

        while (User::where('username', $username)->exists()) {
            $username = $base.'.'.$counter;
            $counter++;
        }

        return $username;
    }
}
