<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    public const STATUS = ['draft', 'published', 'archived'];

    public const PREVIEW_VIDEO_SOURCE_TYPE = [
        'HTML',
        'YouTube',
        'Vimeo',
        'External Link',
        'Embed',
    ];

    protected $fillable = [
        'title',
        'description',
        'price',
        'sale_price',
        'price_start_date',
        'price_end_date',
        'is_free',
        'status',
        'status_start_date',
        'status_end_date',
        'url',
        'level',
        'cover',
        'preview_video_source_type',
        'preview_video_url',
        'preview_video_poster',
        'preview_video_embed',
        'duration',
        'video_duration',
        'course_preview_description',
        'is_featured',
        'is_lock_lessons_in_order',
        'created_by',
    ];



    // Relationships
    public function getCoverAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $folder_path = "business_1/course_{$this->id}";
        return asset("storage/{$folder_path}/{$value}");
    }


    public function categories()
    {
        return $this->belongsToMany(
            CourseCategory::class,   // Related model
            'course_category_courses', // Pivot table name
            'course_id',       // Foreign key on pivot table referencing current model
            'course_category_id',     // Foreign key on pivot table referencing related model
            'id',              // Local key on current model
            'id'               // Local key on related model
        )->withTimestamps();
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

    public function scopeRescrictBeforeEnrollment($query)
    {
        return $query->whereHas("enrollments", function ($q) {
            $q->where("user_id", auth()->user()->id)
                ->where(function ($q) {
                    $q->whereDate('enrolled_at', '<=', now())
                        ->where(function ($q) {
                            $q->whereNull('expiry_date')
                                ->orWhereDate('expiry_date', '>=', now());
                        });
                });
        });
    }
}
