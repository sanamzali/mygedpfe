<?php
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Models\File;
use Illuminate\Http\Request;
use App\Services\ElasticDocumentService;


Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);

Route::get('/ping', fn () => 'pong');

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/version', function () {
    return response()->json(['version' => '1.0.0']);
});

Route::get('/files/{file}/versions', function ($fileId) {
    $file = File::findOrFail($fileId);
    return $file->versions()->orderByDesc('version_number')->get();
});

Route::patch('/files/version/{version}/restore', [FileController::class, 'restoreVersion']);

Route::get('/files/version/{version}/download', [FileController::class, 'downloadVersion']);


Route::get('/search', function (Request $request) {
    $service = new ElasticDocumentService();
    $results = $service->search($request->query('q'));
    return response()->json($results['hits']['hits']);
});

Route::get('/users', function () {
    return User::select('id', 'name', 'email')->get();
});
