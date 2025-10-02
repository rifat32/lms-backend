<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Question extends Model
{
    use HasFactory;

    const TYPES =  ['true_false', 'single', 'multiple', 'matching', 'file_matching', 'keywords', 'fill_in_the_blanks'];

    protected $fillable = [
        'quiz_id',
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

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }
}
