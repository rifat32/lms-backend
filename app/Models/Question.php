<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Question extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

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
        return $this->belongsToMany(QuestionCategory::class, 'question_category_questions', 'question_id', 'question_category_id');
    }


    public function scopeFilters($query)
    {
        return $query->when(request()->filled('question_type'), function ($q) {
            $question_type = request('question_type');

            if (!in_array($question_type, self::TYPES)) {
                throw ValidationException::withMessages([
                    'question_type' => 'Invalid question type value. Allowed values: ' . implode(', ', array_values(self::TYPES))
                ]);
            }

            $q->where('question_type', $question_type);
        });
    }
}
