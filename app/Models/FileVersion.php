<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'version_number',
        'type',
        'path',
        'file_size',
        'uploaded_by',
        'is_final',
        'is_active',
    ];


    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'is_current_version' => 'boolean',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public function file()
    {
        return $this->belongsTo(File::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

}
