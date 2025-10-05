<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'order',
        'course_id',
        'created_by'
    ];



 

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

       public function sectionables()
    {
        return $this->hasMany(Sectionable::class);
    }

    public function lessons()
    {
        return $this->morphedByMany(Lesson::class, 'sectionable', 'sectionables');
    }

    public function quizzes()
    {
        return $this->morphedByMany(Quiz::class, 'sectionable', 'sectionables');
    }



}
