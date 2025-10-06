<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'parent_question_category_id'
    ];

    public function parent()
    {
        return $this->belongsTo(QuestionCategory::class, 'parent_question_category_id');
    }

    public function children()
    {
        return $this->hasMany(QuestionCategory::class, 'parent_question_category_id');
    }
}
