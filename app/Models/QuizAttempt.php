<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        "course_id",
        'quiz_id',
        'user_id',
        'score',
        "total_points",
        'started_at',
        'completed_at',
        "time_spent",
        'is_expired',

        // 
        "attempt_count"
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function quiz_attempt_answers()
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'quiz_attempt_id', 'id');
    }
}
