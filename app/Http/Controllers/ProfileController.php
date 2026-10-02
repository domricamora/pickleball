<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile', [
            'mustVerifyEmail' => true,
            'status' => session('status'),
            'user' => $user->only(['id', 'name', 'email', 'phone', 'skill_level']),
        ]);
    }

    /**
     * Update the signed-in user's own profile. A user may only ever edit their
     * own record — never an id passed in from the client.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'skill_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced', 'competitive'])],
        ]);

        // Changing the address invalidates verification (plan.md §10).
        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }

        $user->fill($validated);
        $user->save();

        return to_route('profile.edit');
    }
}
