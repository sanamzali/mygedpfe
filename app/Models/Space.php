<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

class Space extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'path', 'created_by', 'users'];

    protected $casts = [
        'users' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($space) {
            if (empty($space->slug)) {
                $space->slug = Str::slug($space->name);
            }
            if (empty($space->path)) {
                $space->path = $space->slug;
            }
        });
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
