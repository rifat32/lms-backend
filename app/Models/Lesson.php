<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    public const CONTENT_TYPES = [
        'VIDEO' => 'video',
        'TEXT' => 'text',
        'PDF' => 'pdf',
        'AUDIO' => 'audio',
        'FILE' => 'file',
    ];

    protected $fillable = [
        'course_id',
        'title',
        'content_type',
        'content_url',
        'sort_order',
        'duration',
        'is_preview',
        'is_time_locked',
        'start_date',
        'start_time',
        'unlock_day_after_purchase',
        'description',
        'content',
        'files',
        'materials',

    ];

    /**
     * The attributes that should be cast.
     * * Laravel now handles the JSON decoding of the 'files' attribute.
     * $value passed to the accessor is GUARANTEED to be a PHP array (or null).
     */
    protected $casts = [
        'files' => 'array',
        'materials' => 'array',
    ];


   // In Lesson.php (Model)
public function getFilesAttribute($value)
{
    $files = is_string($value) ? json_decode($value, true) : ($value ?? []);
    $files = is_array($files) ? array_filter($files) : [];
    return array_map(function ($filename) {
        $folder_path = "business_1/lesson_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$filename}");
    }, $files);
}

public function getMaterialsAttribute($value)
{
    $materials = is_string($value) ? json_decode($value, true) : ($value ?? []);
    $materials = is_array($materials) ? array_filter($materials) : [];
    return array_map(function ($filename) {
        $folder_path = "business_1/lesson_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$filename}");
    }, $materials);
}



    public function course()
    {
        return $this->belongsTo(Course::class);
    }


    public function sections()
    {
        return $this->morphToMany(Section::class, 'sectionable');
    }

    public function scopeFilters($query)
    {
        return $query->when(request('course_id'), function ($q, $course_id) {
            return $q->where('course_id', $course_id);
        })->when(request()->filled('search_key'), function ($q) {
            $q->where('title', 'like', '%' . request('search_key') . '%');
        });
    }
}
