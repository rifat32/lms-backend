<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttemptAnswer extends Model
{
    use HasFactory;

  protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'user_answer_ids',
        'correct_answer_ids',
        'is_correct',
    ];

  protected $casts = [
        'user_answer_ids' => 'array',
        'correct_answer_ids' => 'array',
        'is_correct' => 'boolean',
    ];






    

}
