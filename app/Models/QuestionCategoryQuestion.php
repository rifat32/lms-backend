<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionCategoryQuestion extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];
}
