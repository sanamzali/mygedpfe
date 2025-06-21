<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Log;
use Mail;
use Str;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $users = User::all();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return Inertia::render('User/index', [
            'users' => $users,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    /**
     * Display a specific user by ID.
     */
    public function show(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return Inertia::render('User/show', [
            'userData' => $user,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    /**
     * Store a newly created user.
     */

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|string|in:admin,user',
            ]);

            // Generate a random password
            $plainPassword = Str::random(12); // You can change length if needed
            $validated['password'] = Hash::make($plainPassword);

            // Create the user
            $user = User::create($validated);

            event(new Registered($user));

            Log::info('User created: ' . $user->email);

            // Send welcome email with credentials
            try {
                Mail::to($user->email)->send(new WelcomeEmail($user, $plainPassword));
                Log::info('Welcome email sent to: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email to ' . $user->email . ': ' . $e->getMessage());
            }

            return $request->wantsJson()
                ? response()->json($user, 201)
                : Redirect::route('users.index')->with('success', 'User created and email sent.');
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());

            return $request->wantsJson()
                ? response()->json(['error' => 'Failed to create user'], 500)
                : Redirect::back()->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'image' => 'nullable|string',
            'role' => 'nullable|string',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return Redirect::route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Delete the specified user.
     */
    public function delete(int $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return Redirect::route('users.index')->with('success', 'User deleted successfully.');
    }
}
