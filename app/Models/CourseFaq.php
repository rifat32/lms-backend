<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'question',
        'answer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
