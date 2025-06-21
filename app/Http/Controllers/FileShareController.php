<?php

namespace App\Http\Controllers;

use App\Models\FileShare;
use App\Models\File;
use App\Models\Folder;
use App\Models\Project;
use App\Models\Space;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileShareController extends Controller
{
    /**
     * Grant access to file
     */
    public function grantAccess(Request $request)
    {
        $validated = $request->validate([
            'file_id' => 'required|uuid|exists:files,id',
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|string',
            'type' => 'required|string',
        ]);

        $share = FileShare::create([
            'share_uid' => Str::uuid(),
            'shared_with' => $validated['user_id'],
            'file_id' => $validated['file_id'],
            'permissions' => $validated['permissions'],
            'type' => $validated['type'],
            'shared_on' => now(),
            'status' => 'active',
        ]);

        return response()->json($share, 201);
    }

    /**
     * Revoke access from file
     */
    public function revokeAccess($id)
    {
        $share = FileShare::findOrFail($id);
        $share->delete();

        return response()->json(null, 204);
    }

    public function index(Request $request)
    {
        $spaces = Space::all();
        $projects = Project::all();
        $folders = Folder::all();
        $files = File::all();

        if ($request->wantsJson()) {
            return response()->json($spaces);
        }

        return Inertia::render('Share/index', [
            'spaces' => $spaces,
            'projects' => $projects,
            'folders' => $folders,
            'files' => $files,
            'user' => ['role' => Auth::user()->role],
        ]);
    }
}
