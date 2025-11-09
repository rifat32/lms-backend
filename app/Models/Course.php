<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Course extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];
    protected $appends = ['computed_price'];

    public const STATUS = [
        'DRAFT' => 'draft',
        'PUBLISHED' => 'published',
        'ARCHIVED' => 'archived',
    ];

    public const PREVIEW_VIDEO_SOURCE_TYPE = [
        'HTML' => 'HTML',
        'YOUTUBE' => 'YouTube',
        'VIMEO' => 'Vimeo',
        'EXTERNAL' => 'External Link',
        'EMBED' => 'Embed',
    ];

    // public const STATUS = ['draft', 'published', 'archived'];

    // public const PREVIEW_VIDEO_SOURCE_TYPE = [
    //     'HTML',
    //     'YouTube',
    //     'Vimeo',
    //     'External Link',
    //     'Embed',
    // ];

    protected $fillable = [
        'title',
        'description',
        'price',
        'sale_price',
        'price_start_date',
        'price_end_date',
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

    // ✅ Computed attribute
    public function getComputedPriceAttribute()
    {
        $now = now();

        // Check if sale is active
        if (
            $this->sale_price &&
            $this->price_start_date &&
            $this->price_end_date &&
            $now->between($this->price_start_date, $this->price_end_date)
        ) {
            return $this->sale_price;
        }

        // Otherwise return regular price
        return $this->price;
    }

    // Relationships
    public function getCoverAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        $folder_path = "business_1/course_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
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
    public function enrollment()
    {
        return $this->hasOne(Enrollment::class, 'course_id', 'id')
            ->when(auth()->user(), function ($query) {
                $query->where('user_id', auth()->user()->id);
            }, function ($query) {
                $query->where('user_id', -1);
            });
    }

    public function scopeFilters($query)
    {
        return $query
            ->when(request()->filled('searchKey'), function ($q) {
                $q->where('title', 'like', '%' . request('searchKey') . '%');
            })


            ->when(request()->filled('is_enrolled'), function ($q) {
                if (auth()->check()) {
                    $is_enrolled = request()->boolean('is_enrolled');
                    if ($is_enrolled) {
                        $q->whereHas('enrollments', function ($enrollmentQuery) {
                            $enrollmentQuery->where('user_id', auth()->id());
                        });
                    } else {
                        $q->whereDoesntHave('enrollments', function ($enrollmentQuery) {
                            $enrollmentQuery->where('user_id', auth()->id());
                        });
                    }
                }
                // If no authenticated user, do not apply the filter (show all courses)
            })

            ->when(request()->filled('is_featured') || request()->filled('is_popular'), function ($q) {
                $business_settings = BusinessSetting::first();

                $featured_limit = $business_settings?->general__featured_courses_count ?? 10;
                $popular_limit  = $business_settings?->general__popular_courses_count ?? 10;

                // Featured courses
                $q->when(request()->filled('is_featured'), function ($q2) use ($featured_limit) {
                    $q2->when(request()->boolean('is_featured'), function ($q3) use ($featured_limit) {
                        $q3->where('is_featured', 1)
                            ->limit($featured_limit);
                    }, function ($q3) use ($featured_limit) {
                        $q3->where('is_featured', 0)
                            ->limit($featured_limit);
                    });
                });

                // Popular courses
                $q->when(request()->filled('is_popular'), function ($q2) use ($popular_limit) {
                    if (request()->boolean('is_popular')) {
                        $q2->withCount('enrollments')
                            ->orderByDesc('enrollments_count')
                            ->limit($popular_limit);
                    }
                });
            })







            ->when(request()->filled('status'), function ($q) {
                $valid_status = array_values(Course::STATUS);
                $status = request('status');

                if (!in_array($status, $valid_status)) {
                    throw ValidationException::withMessages([
                        'status' => 'Invalid status value. Allowed values: ' . implode(', ', $valid_status),
                    ]);
                }

                $q->where('status', $status);
            })
            ->when(request()->filled('search_key'), function ($q) {
                $q->where('title', 'like', '%' . request('search_key') . '%');
            })

            // ✅ Category filter (many-to-many)
            ->when(request()->filled('category_ids'), function ($q) {
                $category_ids = array_filter(explode(',', request('category_ids'))); // make array and remove empty values

                if (!empty($category_ids)) {
                    $q->whereHas('categories', function ($cat_q) use ($category_ids) {
                        $cat_q->whereIn('course_category_id', $category_ids);
                    });
                }
            })

            // ✅ Level filter
            ->when(request()->filled('level'), function ($q) {
                $levels = explode(',', request('level'));
                $q->whereIn('level', $levels);
            })

            // ✅ Price range filter
            ->when(request()->filled('price_range'), function ($q) {
                $range = explode(',', request('price_range'));

                $start_price = isset($range[0]) && $range[0] !== '' ? (float)$range[0] : null;
                $end_price   = isset($range[1]) && $range[1] !== '' ? (float)$range[1] : null;

                $q->where(function ($price_q) use ($start_price, $end_price) {
                    if (!is_null($start_price)) {
                        $price_q->where(function ($p_q) use ($start_price) {
                            $p_q->whereRaw('
                        CASE
                            WHEN sale_price IS NOT NULL
                                 AND price_start_date IS NOT NULL
                                 AND price_end_date IS NOT NULL
                                 AND NOW() BETWEEN price_start_date AND price_end_date
                            THEN sale_price
                            ELSE price
                        END >= ?
                    ', [$start_price]);
                        });
                    }

                    if (!is_null($end_price)) {
                        $price_q->where(function ($p_q) use ($end_price) {
                            $p_q->whereRaw('
                        CASE
                            WHEN sale_price IS NOT NULL
                                 AND price_start_date IS NOT NULL
                                 AND price_end_date IS NOT NULL
                                 AND NOW() BETWEEN price_start_date AND price_end_date
                            THEN sale_price
                            ELSE price
                        END <= ?
                    ', [$end_price]);
                        });
                    }
                });
            });
    }

    public function scopeRestrictBeforeEnrollment($query)
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
