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
        'duration',
        'is_preview',
        'is_time_locked',
        'start_date',
        'start_time',
        'unlock_day_after_purchase',
        'description',
        'content',
        'files',

    ];

    /**
     * The attributes that should be cast.
     * * Laravel now handles the JSON decoding of the 'files' attribute.
     * $value passed to the accessor is GUARANTEED to be a PHP array (or null).
     */
    protected $casts = [
        'files' => 'array',
    ];

    
   public function getFilesAttribute($value)
{
    // Decode only if it’s a string
    $files = is_string($value) ? json_decode($value, true) : ($value ?? []);

    // Ensure it's always an array
    $files = array_filter($files ?? []);

    return array_map(function ($filename) {
        $folder_path = "business_1/lesson_{$this->id}";
        return asset("storage/{$folder_path}/{$filename}");
    }, $files);
}

    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

   
    public function sections()
    {
        return $this->morphToMany(Section::class, 'sectionable');
    }
}