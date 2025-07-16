<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'attachment_path',
        'is_locked',
        'lock_type',
        'pin',
        'face_data',
    ];

    protected $hidden = [
        'pin',
        'face_data',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'pin' => 'hashed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
