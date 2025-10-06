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

    const TYPES =  ['true_false', 'single', 'multiple', 'matching', 'file_matching', 'keywords', 'fill_in_the_blanks'];

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
        return $this->belongsToMany(QuestionCategory::class, 'question_category_questions', 'question_id', 'question_category_id');
    }
}
