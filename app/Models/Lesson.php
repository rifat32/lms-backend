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
    'content_type',
    'content_url',
    'sort_order',
    'section_id',
    'duration',
    'is_preview',
    'is_time_locked',
    'start_date',
    'start_time',
    'unlock_day_after_purchase',
    'description',
    'content',
    'files'
];

protected $casts = [
    'files' => 'array',
];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
