<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FileShareController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\UserController;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/spaces', [SpaceController::class, 'index'])->name('spaces.index');
Route::get('/spaces/{slug}', [SpaceController::class, 'show'])->name('spaces.show');
Route::post('/spaces', [SpaceController::class, 'store'])->name('spaces.store');
Route::put('/spaces/{space}', [SpaceController::class, 'update'])->name('spaces.update');
Route::delete('/spaces/{id}', [SpaceController::class, 'delete'])->name('spaces.delete');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/spaces/{space}/{slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{id}', [ProjectController::class, 'delete'])->name('projects.delete');

Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
Route::get('/spaces/{space}/{project}/{slug}', [FolderController::class, 'show'])->name('folders.show');
Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
Route::delete('/folders/{id}', [FolderController::class, 'delete'])->name('folders.delete');

Route::post('/files/upload', [FileController::class, 'uploadFile']);
Route::get('/files/folder/{folderId}', [FileController::class, 'getFilesByFolderId']);
Route::delete('/files/{id}', [FileController::class, 'deleteFile'])->name('files.delete');
Route::get('/files/download/{id}', [FileController::class, 'downloadFile']);
Route::post('/files/version', [FileController::class, 'uploadNewVersion'])->name('files.version');
Route::patch('/files/version/{version}/restore', [FileController::class, 'restoreVersion'])->name('files.version.restore');


// Route::get('/files', [FileController::class, 'index'])->name('files.index');
// Route::get('/files/{slug}', [FileController::class, 'show'])->name('files.show');
// Route::post('/files', [FileController::class, 'store'])->name('files.store');
// Route::put('/files/{file}', [FileController::class, 'update'])->name('files.update');
// Route::delete('/files/{id}', [FileController::class, 'delete'])->name('files.delete');

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{slug}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::put('/users/{file}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{id}', [UserController::class, 'delete'])->name('users.delete');

// Route::get('/shared', ['uses' => FileController::class . '@sharedFiles',])->name('files.shared');
Route::get('/shared', [FileShareController::class, 'index'])->name('shared.index');
Route::post('/spaces/{slug}/share', [SpaceController::class, 'share'])->name('spaces.share');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
