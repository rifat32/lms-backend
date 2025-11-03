<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public const DISCOUNT_TYPE = [
        'PERCENTAGE' => 'percentage',
        'FIXED' => 'fixed',
    ];

    protected $fillable = [
        'name',
        'code',
        'discount_type',      // 'percent' or 'flat'
        'discount_amount',    // numeric
        'min_total',          // optional minimum purchase
        'max_total',          // optional max discount
        'redemptions',        // total number of times coupon can be used
        'coupon_start_date',
        'coupon_end_date',
        'is_auto_apply',
        'is_active',
    ];

    public static function getDiscountTypes(): array
    {
        return array_values(self::DISCOUNT_TYPE);
    }
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'coupon_courses', 'coupon_id', 'course_id');
    }
}
