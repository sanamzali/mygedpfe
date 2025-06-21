<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'share_uid',
        'shared_with',
        'file_id',
        'permissions',
        'shared_on',
        'type',
        'status',
        'expiration_date',
    ];

    protected $casts = [
        'shared_on' => 'date',
        'expiration_date' => 'date',
    ];

    

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function sharedWith()
    {
        return $this->belongsTo(User::class, 'shared_with');
    }
}