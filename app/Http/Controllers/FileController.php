<?php

namespace App\Http\Controllers;

use App\Services\ElasticDocumentService;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\Folder;
use Illuminate\Support\Facades\Log;
use App\Models\Project;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Redirect;
use Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\DocumentIndexer;
use Elasticsearch\Client;


class FileController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'required|uuid|exists:folders,id',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = File::where('folder_id', $request->folder_id)
            ->with(['folder', 'folder.space', 'folder.project', 'activeVersion']);

        if ($request->has('search')) {
            $query->where('filename', 'like', '%' . $request->search . '%');
        }

        $files = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'files' => $files,
            'user' => [
                'id' => $request->user()->id,
                'role' => $request->user()->role,
            ],
        ]);
    }

    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|max:' . (1024 * 50), // 50MB max
            'folder_id' => 'nullable|exists:folders,id',
            'is_encrypted' => 'nullable|boolean',
            'password' => 'nullable|string|min:6|required_if:is_encrypted,1',
            'description' => 'nullable|string|max:500',
            // 'project_id' => 'nullable|exists:projects,id',
            // 'space_id' => 'nullable|exists:spaces,id',
            'is_indexed' => 'nullable|boolean',
            'is_deleted' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $folder = Folder::findOrFail($request->folder_id);
        // $project = Project::findOrFail($request->project_id);
        // $space = Space::findOrFail($request->space_id);
        $uploadedFile = $request->file('file');

        $filename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $uploadedFile->getClientOriginalExtension();
        $uniqueFilename = Str::slug($filename) . '-' . uniqid() . '.' . $extension;

        $path = $uploadedFile->storeAs('files/' . $folder->id, $uniqueFilename);

        $file = File::create([
            'filename' => $uploadedFile->getClientOriginalName(),
            'file_type' => $uploadedFile->getClientMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'file_path' => $path,
            'storage_path' => $path,
            'folder_id' => $folder->id,
            // 'project_id' => $project->id,
            // 'space_id' => $space->id,
            'is_encrypted' => $request->is_encrypted ?? false,
            'password' => $request->password ? bcrypt($request->password) : null,
            'description' => $request->description,
            // 'uploaded_by' => $request->user()->id,
            'created_by' => $request->user()->id,
            'users' => [$request->user()->id],
        ]);

        //Indexer le document dans Elasticsearch
        $service = new ElasticDocumentService();
        $service->indexDocument($file);


        try {
            $service = new \App\Services\ElasticDocumentService();
            $service->indexDocument($file);
        } catch (\Exception $e) {
            Log::error('Erreur Elasticsearch : ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'File uploaded successfully',
                'file' => $file->load(['folder', 'folder.space', 'folder.project']),
                'user' => [
                    'id' => $request->user()->id,
                    'role' => $request->user()->role,
                ],
            ], 201);
        }


        return back()->with('success', 'File uploaded successfully');
    }



    public function downloadVersion(FileVersion $version): StreamedResponse
    {
        if (!Storage::exists($version->path)) {
            abort(404, 'File not found');
        }

        $filename = 'version_' . $version->version_number . '.' . $version->type;

        return Storage::download($version->path, $filename);
    }

    public function uploadNewVersion(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'original_file_id' => 'required|exists:files,id',
        ]);

        $originalFile = File::findOrFail($request->original_file_id);
        $newFile = $request->file('file');
        $path = $newFile->store('files');

        $version = FileVersion::create([
            'file_id' => $originalFile->id,
            'version_number' => $originalFile->versions()->count() + 1,
            'type' => $newFile->getClientOriginalExtension(),
            'path' => $path,
            'file_size' => $newFile->getSize(),
            'uploaded_by' => auth()->id(),
            'is_final' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Version uploaded',
            'version' => $version
        ]);
    }

    public function restoreVersion(FileVersion $version)
    {
        // Désactiver toutes les autres versions du fichier
        FileVersion::where('file_id', $version->file_id)->update(['is_active' => false]);

        // Activer la version restaurée
        $version->update(['is_active' => true]);

        return response()->json(['message' => 'Version restored successfully']);
    }


    public function getFilesByFolderId(Request $request, $folderId)
    {
        $perPage = $request->input('per_page', 10);
        $files = File::where('folder_id', $folderId)->paginate($perPage);
        return response()->json($files);
    }

    public function downloadFile($id)
    {
        $file = File::findOrFail($id);

        if (!Storage::exists($file->storage_path)) {
            return response()->json(['message' => 'File not found in storage'], 404);
        }

        return Storage::download($file->storage_path, $file->filename);
    }

    public function getFile($id)
    {
        $file = File::with(['folder', 'folder.space', 'folder.project'])
            ->findOrFail($id);

        return response()->json($file);
    }

    public function updateFile(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_encrypted' => 'sometimes|boolean',
            'password' => 'nullable|string|min:6|required_if:is_encrypted,true',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = File::findOrFail($id);

        $updateData = [
            'filename' => $request->filename ?? $file->filename,
            'description' => $request->description ?? $file->description,
            'is_encrypted' => $request->has('is_encrypted')
                ? $request->is_encrypted
                : $file->is_encrypted,
        ];

        if ($request->has('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $file->update($updateData);

        return response()->json([
            'message' => 'File updated successfully',
            'file' => $file->fresh()->load(['folder', 'folder.space', 'folder.project'])
        ]);
    }

    public function deleteFile($id)
    {
        $file = File::findOrFail($id);

        if (!in_array(auth()->id(), $file->users ?? [])) {
            abort(403, 'You do not have permission to delete this file.');
        }

        Storage::delete($file->storage_path);
        $file->delete();

        if (request()->wantsJson()) {
            return response()->noContent();
        }

        return Redirect::back()->with('success', 'File deleted successfully');
    }

    public function previewFile($id)
    {
        $file = File::findOrFail($id);

        if (!Storage::exists($file->storage_path)) {
            return response()->json(['message' => 'File not found in storage'], 404);
        }

        $previewableTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

        if (!in_array($file->file_type, $previewableTypes)) {
            return response()->json(['message' => 'File type not previewable'], 400);
        }

        return response()->file(Storage::path($file->storage_path));
    }
}
