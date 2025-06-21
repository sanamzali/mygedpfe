<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Redirect;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with('space')->get();

        if ($request->wantsJson()) {
            return response()->json($projects);
        }

        return Inertia::render('Project/index', [
            'projects' => $projects,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    public function show(Request $request, $space, $slug)
    {
        $project = Project::where('slug', $slug)
            ->whereHas('space', fn($q) => $q->where('slug', $space))
            ->with([
                'space',
                'folders' => fn($query) => $query->whereNull('parent_id')
            ])
            ->firstOrFail();

        if (!in_array(auth()->id(), $project->users ?? [])) {
            abort(403, 'You do not have access to this project.');
        }

        if ($request->wantsJson()) {
            return response()->json($project);
        }

        return Inertia::render('Project/show', [
            'project' => $project,
            'space' => $project->space,
            'folders' => $project->folders,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'nullable|string',
            'storage' => 'nullable|string',
            'space_id' => 'required|exists:spaces,id',
            'status' => 'required|in:active,archived',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['created_by'] = auth()->id();
        $validated['users'] = [auth()->id()];

        $project = Project::create($validated);

        if ($request->wantsJson()) {
            return response()->json($project, 201);
        }

        return Redirect::route('spaces.show', ['slug' => $project->space->slug])
            ->with('success', 'Project created successfully.');
    }


    public function update(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if (!in_array(auth()->id(), $project->users ?? [])) {
            abort(403, 'You do not have permission to update this project.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'nullable|string',
            'storage' => 'nullable|string',
            'space_id' => 'sometimes|exists:spaces,id',
            'status' => 'sometimes|in:active,archived',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $project->update($validated);

        if ($request->wantsJson()) {
            return response()->json($project);
        }

        return Redirect::route('spaces.show', ['slug' => $project->space->slug])
            ->with('success', 'Project updated successfully.');
    }

public function delete(string $slug)
{
    $project = Project::where('slug', $slug)->firstOrFail();

    if (!in_array(auth()->id(), $project->users ?? [])) {
        abort(403, 'You do not have permission to delete this project.');
    }

    // 🔒 Interdire suppression si actif
    if ($project->status === 'active') {
        return Redirect::back()->withErrors(['error' => 'Impossible de supprimer un projet actif.']);
    }

    $project->folders()->each(function ($folder) {
        $folder->files()->delete();
        $folder->delete();
    });

    $project->folders()->delete();
    $project->delete();

    return Redirect::route('spaces.show', ['slug' => $project->space->slug])
        ->with('success', 'Project deleted successfully.');
}

}
