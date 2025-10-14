<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonProgress extends Model
{

    use HasFactory;
    protected $hidden = ['pivot'];
    protected $table = "lesson_progresses";

    protected $fillable = [
        'user_id',
        'course_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LessonSession::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->where('user_id', auth()->user()->id);
    }
}
