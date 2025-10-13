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

    public const  PREVIEW_VIDEO_SOURCE_TYPE = [
        'HTML' => 'html',
        'YOUTUBE' => 'youtube',
        'VIMEO' => 'vimeo',
        'EXTERNAL_LINK' => 'external_link',
        'EMBED' => 'embed',
    ];

    protected $fillable = [
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

        // new fields
        "subtitle",
        "video_width",
        "required_progress",
        "preview_video_source_type",
        "preview_video_url",
        "preview_video_poster",
        "preview_video_embed",
        "pdf_read_completion_required",

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

    public function getPreviewVideoUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($this->preview_video_source_type !== 'html') {
            return $value;
        }



        $folder_path = "business_1/lesson_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }
    public function getPreviewVideoPosterAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $folder_path = "business_1/lesson_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }
    public function getSubtitleAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $folder_path = "business_1/lesson_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
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
