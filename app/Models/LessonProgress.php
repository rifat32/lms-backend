<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{

    use HasFactory;
    protected $hidden = ['pivot'];
    protected $table = "lesson_progresses";

    protected $fillable = [
        'user_id',
        'lesson_id',
        'total_time_spent',
        'is_completed',
        'last_accessed',
    ];

    protected $casts = [
        'total_time_spent' => 'integer',
        'is_completed' => 'boolean',
        'last_accessed' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(\App\Models\Lesson::class);
    }

    public function sessions()
    {
        return $this->hasMany(LessonSession::class);
    }
}
