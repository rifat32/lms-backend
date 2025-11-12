<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $hidden = ['pivot'];

    protected $fillable = [
        'user_id',
        'course_id',
        'amount',
        'original_price',
        'method',
        'status',
        'transaction_id',
        'payment_intent_id',
        'paid_at',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
