<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        'name',
        'designation',
        'bio',
        'photo_url',
        'email',
    ];
}
