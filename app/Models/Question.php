<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Question extends Model
{
    use HasFactory;

    const TYPES =  [
        'TRUE_FALSE' => 'true_false',
        'SINGLE' => 'single',
        'MULTIPLE' => 'multiple',
        'MATCHING' => 'matching',
        'FILE_MATCHING' => 'file_matching',
        'KEYWORDS' => 'keywords',
        'FILL_IN_THE_BLANKS' => 'fill_in_the_blanks'
    ];

    protected $fillable = [
        'question_text',
        'question_type',
        'points',
        'time_limit',
        'is_required'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'points' => 'integer',
        'time_limit' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'question_id', 'id');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions', 'question_id', 'quiz_id');
    }


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(QuestionCategory::class, 'question_category_questions', 'question_id', 'category_id');
    }
}
