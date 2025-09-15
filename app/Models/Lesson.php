<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

  
    protected $fillable = [
        'course_id',
        'title',
        'content_type',   // e.g. video, pdf, quiz, text
        'content_url',
        'sort_order',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
