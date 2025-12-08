<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Quiz extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    public const TIME_UNITS = [
        'HOURS' => 'Hours',
        'MINUTES' => 'Minutes'
    ];
    public const STYLES = [
        'pagination',
        'all-in-one'
    ];

    protected $fillable = [
        'title',
        'description',
        'time_limit',
        'time_unit',
        'style',
        'is_randomized',

        'allow_retake_after_pass',
        'max_attempts',
        'points_cut_after_retake',
        'passing_grade',
        'question_limit',
        'show_correct_answer',
    ];




    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_questions', 'quiz_id', 'question_id');
    }


    public function sections()
    {
        return $this->morphToMany(Section::class, 'sectionable');
    }

    public function all_quiz_attempts()
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }
    public function quiz_attempts()
    {
        return $this->hasOne(QuizAttempt::class, 'quiz_id')
            ->when(auth()->user(), function ($query) {
                $query->where('quiz_attempts.user_id', auth()->user()->id);
            }, function ($query) {
                $query->where('quiz_attempts.user_id', -1);
            })

            ->orderByDesc('quiz_attempts.id');
    }
}
