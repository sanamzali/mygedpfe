<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Space;
use Auth;
use File;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Redirect;
use Validator;
use App\Services\ElasticDocumentService;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        $folders = Folder::with(['space', 'project'])->get();

        if ($request->wantsJson()) {
            return response()->json($folders);
        }

        return Inertia::render('Folder/index', [
            'folders' => $folders,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    public function show(Request $request, $space, $project, $slug)
    {
        $project = Project::where('slug', $project)->firstOrFail();
        $project->load('folders');

        $folder = Folder::where('slug', $slug)
            ->with(['parent', 'children', 'project', 'files', 'updatedBy'])
            ->firstOrFail();

        if (!in_array(auth()->id(), $folder->users ?? [])) {
            abort(403, 'You do not have permission to access this folder.');
        }

        if ($request->wantsJson()) {
            return response()->json($folder);
        }

        return Inertia::render('Folder/show', [
            'space' => $folder->project->space,
            'project' => $project,
            'folder' => $folder,
            'user' => ['role' => Auth::user()->role],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:folders,id',
            'space_id' => 'nullable|exists:spaces,id',
            'project_id' => 'nullable|exists:projects,id',
            'origin' => 'nullable|string',
            'size' => 'nullable|integer',
            'is_downloadable' => 'boolean',
            'icon' => 'nullable|string',
            'status' => 'nullable|string',
            'updated_by' => 'nullable|exists:users,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['created_by'] = auth()->id();            // ✅ add creator
        $validated['users'] = [auth()->id()];               // ✅ default access

        $folder = Folder::create($validated);

        if ($request->wantsJson()) {
            return response()->json($folder, 201);
        }

        return Redirect::route('folders.show', [
            'slug' => $validated['slug'],
            'space' => $folder->project->space->slug,
            'project' => $folder->project->slug
        ])->with('success', 'Folder created successfully.');
    }

    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|max:' . (1024 * 50),
            'folder_id' => 'nullable|exists:folders,id',
            'project_id' => 'required|exists:projects,id',
            'space_id' => 'nullable|exists:spaces,id',
            'is_encrypted' => 'nullable|boolean',
            'password' => 'nullable|string|min:6|required_if:is_encrypted,true',
            'description' => 'nullable|string|max:500',
            'is_indexed' => 'nullable|boolean',
            'is_deleted' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $folder = Folder::findOrFail($request->folder_id);
        $project = Project::findOrFail($request->project_id);
        $space = Space::findOrFail($request->space_id);
        $uploadedFile = $request->file('file');

        $filename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $uploadedFile->getClientOriginalExtension();
        $uniqueFilename = Str::slug($filename) . '-' . uniqid() . '.' . $extension;

        $path = $uploadedFile->storeAs('files/' . $folder->id, $uniqueFilename);

        $file = File::create([
            'filename' => $uploadedFile->getClientOriginalName(),
            'unique_filename' => $uniqueFilename,
            'file_type' => $uploadedFile->getClientMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'file_path' => $path,
            'storage_path' => $path,
            'folder_id' => $folder->id,
            'project_id' => $project->id,
            'space_id' => $space->id,
            'is_encrypted' => $request->is_encrypted ?? false,
            'password' => $request->password ? bcrypt($request->password) : null,
            'description' => $request->description,
            'uploaded_by' => $request->user()->id,
            'created_by' => $request->user()->id,
            'users' => [$request->user()->id],
        ]);


        return response()->json([
            'message' => 'File uploaded successfully',
            'file' => $file->load(['folder', 'folder.space', 'folder.project'])
        ], 201);
    }

    public function delete(string $slug)
    {
        $folder = Folder::where('slug', $slug)->firstOrFail();

        if (!in_array(auth()->id(), $folder->users ?? [])) {
            abort(403, 'You do not have permission to delete this folder.');
        }

        $this->deleteFolderAndContents($folder);

        return Redirect::route('projects.show', [
            'slug' => $folder->project->slug,
            'space' => $folder->project->space->slug
        ])->with('success', 'Folder and all its contents deleted successfully.');
    }

    private function deleteFolderAndContents(Folder $folder)
    {
        foreach ($folder->folders as $subfolder) {
            $this->deleteFolderAndContents($subfolder);
        }

        $folder->files()->delete();
        $folder->delete();
    }
}
