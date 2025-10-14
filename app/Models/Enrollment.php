<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        'course_id',
        'user_id',
        'enrolled_at',
        'expiry_date',
        'progress',
        'status',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id',);
    }
}
