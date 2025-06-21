<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

class Project extends Model
{
    use HasFactory;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    protected $fillable = [
        'name', 'slug', 'description', 'storage', 'path', 'status',
        'is_archived', 'space_id', 'created_by', 'users',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'users' => 'array',
    ];

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }

            if ($project->space || $project->space_id) {
                $space = $project->space ?: Space::find($project->space_id);
                if ($space) {
                    $project->path = $space->slug . '/' . $project->slug;
                }
            }
        });

        static::updating(function ($project) {
            if ($project->isDirty(['slug', 'space_id'])) {
                $space = $project->space ?: Space::find($project->space_id);
                if ($space) {
                    $project->path = $space->slug . '/' . $project->slug;
                }
            }
        });
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
