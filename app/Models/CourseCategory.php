<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCategory extends Model
{
  use HasFactory;

  // The attributes that are mass assignable.
  protected $fillable = [
    'name',
    'description',
    'parent_id',
  ];


  /**
   * Parent category relationship
   */
  public function parent(): BelongsTo
  {
    return $this->belongsTo(CourseCategory::class, 'parent_id');
  }

  // RELATIONSHIPS
  public function courses()
  {
    return $this->belongsToMany(
      Course::class,   // Related model
      'course_category_courses', // Pivot table name
      'course_category_id',       // Foreign key on pivot table referencing current model
      'course_id',     // Foreign key on pivot table referencing related model
      'id',              // Local key on current model
      'id'               // Local key on related model
    )->withTimestamps();
  }


  // FILTERS
  public function scopeFilters($query)
  {
    return $query->when(request()->filled('searchKey'), function ($q) {
      $q->where('name', 'like', '%' . request('searchKey') . '%');
    });
  }
}
