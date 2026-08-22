<?php

namespace App\Http\Controllers;

use App\Models\ClientPortalInvitation;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PortalController extends Controller
{
    public function activateForm(string $token)
    {
        abort_unless(Schema::hasTable('client_portal_invitations'), 404);
        $invitation = ClientPortalInvitation::where('token', $token)->where('status', 'Invited')->firstOrFail();

        return view('portal.activate', compact('invitation'));
    }

    public function activate(Request $request, string $token)
    {
        abort_unless(Schema::hasTable('client_portal_invitations'), 404);
        $invitation = ClientPortalInvitation::where('token', $token)->where('status', 'Invited')->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::firstOrCreate(
            ['email' => $invitation->email],
            [
                'name' => $data['name'],
                'username' => $invitation->email,
                'role' => 'client_portal',
                'is_active' => true,
                'password' => Hash::make($data['password']),
            ]
        );

        $invitation->update(['user_id' => $user->id, 'status' => 'Activated', 'activated_at' => now()]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }

    public function dashboard()
    {
        abort_unless(Schema::hasTable('client_portal_invitations'), 404);
        $invitation = ClientPortalInvitation::where('user_id', Auth::id())->latest()->firstOrFail();
        $clientId = $invitation->client_id;

        return view('portal.dashboard', [
            'invitation' => $invitation->load('client'),
            'projects' => Project::where('client_id', $clientId)->with('documents', 'warranties')->latest()->get(),
            'invoices' => Invoice::where('client_id', $clientId)->source()->latest()->get(),
        ]);
    }
}
