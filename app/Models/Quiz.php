<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'time_limit',
        'is_randomized',
        'question_limit',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class, "id", "quiz_id");
    }
}
