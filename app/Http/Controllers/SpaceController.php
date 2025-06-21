<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $spaces = Space::all();

        if ($request->wantsJson()) {
            return response()->json($spaces);
        }

        return Inertia::render('Space/index', [
            'spaces' => $spaces,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $space = Space::where('slug', $slug)
            ->with('projects')
            ->firstOrFail();

        if (!in_array(auth()->id(), $space->users ?? [])) {
            abort(403, 'You do not have access to this space.');
        }

        if ($request->wantsJson()) {
            return response()->json($space);
        }

        return Inertia::render('Space/show', [
            'space' => $space,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['created_by'] = auth()->id();
        $validated['users'] = [auth()->id()];

        $space = Space::create($validated);

        if ($request->wantsJson()) {
            return response()->json($space, 201);
        }

        return Redirect::route('spaces.index')->with('success', 'Space created successfully.');
    }

    public function update(Request $request, string $slug)
    {
        $space = Space::where('slug', $slug)->firstOrFail();

        if (!in_array(auth()->id(), $space->users ?? [])) {
            abort(403, 'You do not have permission to update this space.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'nullable|string',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $space->update($validated);

        if ($request->wantsJson()) {
            return response()->json($space);
        }

        return Redirect::route('spaces.index')->with('success', 'Space updated successfully.');
    }

    public function delete(string $slug)
    {
        $space = Space::where('slug', $slug)->firstOrFail();

        if (!in_array(auth()->id(), $space->users ?? [])) {
            abort(403, 'You do not have permission to delete this space.');
        }

        $space->projects()->delete();
        $space->delete();

        return Redirect::route('spaces.index')->with('success', 'Space deleted successfully.');
    }

    public function share(Request $request, string $slug)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $space = Space::where('slug', $slug)->firstOrFail();

        if (!in_array(auth()->id(), $space->users ?? [])) {
            abort(403, 'You do not have permission to share this space.');
        }

        $space->users = array_unique(array_merge($space->users ?? [], $request->user_ids));
        $space->save();

        return back()->with('success', 'Users shared successfully.');
    }

}
