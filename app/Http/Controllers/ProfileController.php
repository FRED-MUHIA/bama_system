<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
