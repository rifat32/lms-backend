<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
         protected $fillable = [
        'title',
        'description',
        'price',
        'category_id',
    ];

    // Relationships
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    public function notices()
    {
        return $this->hasMany(Notice::class);
    }

    public function reviews()
{
    return $this->hasMany(CourseReview::class, 'course_id', 'id');
}

public function payments()
{
    return $this->hasMany(Payment::class, 'course_id', 'id');
}

public function enrollments()
{
    return $this->hasMany(Enrollment::class, 'course_id', 'id');
}
}
