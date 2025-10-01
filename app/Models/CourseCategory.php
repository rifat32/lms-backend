<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
  use HasFactory;

  // The attributes that are mass assignable.
  protected $fillable = [
    'name',
  ];


  // RELATIONSHIPS
  public function courses()
  {
    return $this->hasMany(Course::class, 'category_id');
  }


  // FILTERS
  public function scopeFilters($query)
  {
    return $query->when(request()->filled('searchKey'), function ($q) {
      $q->where('name', 'like', '%' . request('searchKey') . '%');
    });
  }
}
