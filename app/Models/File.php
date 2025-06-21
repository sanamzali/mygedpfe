<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'unique_filename',
        'file_type',
        'file_size',
        'file_path',
        'storage_path',
        'folder_id',
        // 'project_id',
        // 'space_id',
        'is_encrypted',
        'password',
        // 'description',
        // 'uploaded_by',
        'created_by',
        'users',
    ];

    protected $casts = [
        'users' => 'array',
        'is_encrypted' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function shares()
    {
        return $this->hasMany(FileShare::class);
    }

    public function versions()
    {
        return $this->hasMany(FileVersion::class)->orderByDesc('version_number');
    }

    public function isAccessibleBy(User $user): bool
    {
        return in_array($user->id, $this->users ?? []);
    }

    public function latestVersion()
    {
        return $this->hasOne(FileVersion::class)->latestOfMany('version_number');
    }

    public function activeVersion()
    {
        return $this->hasOne(FileVersion::class)->where('is_active', true);
    }


}
