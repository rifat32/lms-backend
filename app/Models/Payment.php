<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

     protected $fillable = [
        'user_id',
        'course_id',
        'amount',
        'method',
        'status',
        'transaction_id',
        'paid_at',
    ];

    public function course()
{
    return $this->belongsTo(Course::class, 'course_id', 'id');
}
}
