<?php

namespace App\Http\Controllers;

use App\Models\FileVersion;
use File;
use Illuminate\Http\Request;
use Storage;

class FileVersionController extends Controller
{
    /**
     * Create a new file version
     */
    public function createVersion(Request $request)
    {
        $validated = $request->validate([
            'file_id' => 'required|uuid|exists:files,id',
            'description' => 'required|string',
            'type' => 'required|string',
            'size' => 'required|integer',
            'is_public' => 'sometimes|boolean',
        ]);

        // Get current version number
        $versionNumber = FileVersion::where('file_id', $validated['file_id'])
            ->max('version_number') ?? 0;
        $versionNumber++;

        // Mark all previous versions as not current
        FileVersion::where('file_id', $validated['file_id'])
            ->update(['is_current_version' => false]);

        $version = FileVersion::create([
            'file_id' => $validated['file_id'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'size' => $validated['size'],
            'version_number' => $versionNumber,
            'is_current_version' => true,
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json($version, 201);
    }
        public function index(File $file)
    {
        return $file->versions()->with('user')->get();
    }

    public function download(FileVersion $version)
    {
        return Storage::download($version->path);
    }

    public function rollback(FileVersion $version)
    {
        $file = $version->file;
        $file->update(['path' => $version->path]);

        return response()->json(['message' => 'Version restaurée comme actuelle.']);
    }
}
