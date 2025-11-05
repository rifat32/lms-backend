<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
    protected $casts = [
        'is_active'         => 'bool',
        'coupon_start_date' => 'date',
        'coupon_end_date'   => 'date',
    ];

    public static function getDiscountTypes(): array
    {
        return array_values(self::DISCOUNT_TYPE);
    }
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'coupon_courses', 'coupon_id', 'course_id');
    }

    public function scopeFilters(Builder $query): Builder
    {
        // SEARCH
        if (!empty(request()->filled('search_key'))) {
            $search_key = request('search_key');
            $query->where(function (Builder $qq) use ($search_key) {
                $qq->where('name', 'like', "%{$search_key}%");
            });
        }

        // IS_ACTIVE (supports "1"/"0", true/false)
        if (!empty(request()->filled('is_active'))) {
            $query->where('is_active', request('is_active'));
        }

        // DATE RANGE (inclusive)
        if (!empty(request()->filled('start_date'))) {
            $query->whereDate('coupon_start_date', '>=', request('start_date'));
        }
        if (!empty(request()->filled('end_date'))) {
            $query->whereDate('coupon_end_date', '<=', request('end_date'));
        }

        return $query;
    }

    /**
     * "Currently active" means: flagged active AND within start/end window (if set).
     */
    public function scopeActive(Builder $q): Builder
    {
        $today = Carbon::today();

        return $q->where('is_active', true)
            ->where(function (Builder $qq) use ($today) {
                // start is null or <= today
                $qq->whereNull('coupon_start_date')
                    ->orWhereDate('coupon_start_date', '<=', $today);
            })
            ->where(function (Builder $qq) use ($today) {
                // end is null or >= today
                $qq->whereNull('coupon_end_date')
                    ->orWhereDate('coupon_end_date', '>=', $today);
            });
    }
}
