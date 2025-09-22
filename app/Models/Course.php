<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'price',
        'category_id',
        'is_free',
        'status',
        'url',
        'level',
        'cover',
        'preview_video',
        'duration',
        'video_duration',
        'course_preview_description',
        'course_status',
        'status_start_date',
        'status_end_date',
        'number_of_students',
        'course_view',
        'is_featured',
        'is_lock_lessons_in_order',
        'access_duration',
        'access_device_type',
        'certificate_info',
        'pricing',
        'created_by',
    ];


    // Relationships

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'course_id'); // foreign key in sections table
    }

    // public function faqs()
    // {
    //     return $this->hasMany(Faq::class);
    // }

    // public function notices()
    // {
    //     return $this->hasMany(Notice::class);
    // }

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

    public function scopeFilters($query)
    {
        return $query->when(request()->filled('searchKey'), function ($q) {
            $q->where('title', 'like', '%' . request('searchKey') . '%');
        })->when(request()->filled('category_id'), function ($q) {
            $q->where('category_id', request('category_id'));
        });
    }
}
