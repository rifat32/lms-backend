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
        'show_correct_answer',
        'allow_retake_after_pass',
        'max_attempts',
        'points_cut_after_retake',
        'passing_grade',
        'question_limit',
    ];




    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_questions', 'quiz_id', 'question_id');
    }


    public function sections()
    {
        return $this->morphToMany(Section::class, 'sectionable');
    }
}
