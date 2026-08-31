<?php

namespace App\Http\Controllers;

use App\Services\AccountEmailReuseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'phone' => ['nullable', 'max:100'],
            'preferred_language' => ['required', 'in:en,sw'],
            'timezone' => ['required', 'timezone'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['boolean'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        foreach (['photo', 'signature'] as $field) {
            if (! $request->hasFile($field)) continue;
            $column = $field.'_path';
            if ($user->$column) Storage::disk('public')->delete($user->$column);
            $data[$column] = $request->file($field)->store('users/'.$field, 'public');
        }

        $preferences = $request->input('notification_preferences', []);
        $data['notification_preferences'] = collect(['email', 'approvals', 'projects', 'security'])
            ->mapWithKeys(fn ($key) => [$key => (bool) ($preferences[$key] ?? false)])->all();

        unset($data['photo'], $data['signature']);
        $user->update($data);

        return back()->with('status', 'Profile preferences updated. Access assignments were unchanged.');
    }

    public function destroy(Request $request, AccountEmailReuseService $emailReuse)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_delete' => ['accepted'],
        ]);

        $user = $request->user();

        if ($user->role === 'super_admin') {
            throw ValidationException::withMessages([
                'current_password' => 'Owner console accounts cannot be self-deleted from this page.',
            ]);
        }

        DB::transaction(fn () => $emailReuse->holdSelfDeletedAccount($user));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account has been deleted. This email can be used for a new account after four months.');
    }
}
