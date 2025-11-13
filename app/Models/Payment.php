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
        'coupon_code',
        'discount_amount',
    ];

    /**
     * Machine value => Human label
     * (Keep keys EXACTLY as stored in DB.)
     */
    public const STATUS_OPTIONS = [
        'Pending' => 'pending',
        'Completed' => 'completed',
        'Failed' => 'failed',
    ];

    /** Only the DB values (useful for Rule::in). */
    public static function statusValues(): array
    {
        return array_values(self::STATUS_OPTIONS);
    }

    /**
     * Get validation rules for the status field
     */
    public static function statusValidationRule(): string
    {
        return 'required|in:' . implode(',', self::statusValues());
    }

    /**
     * Set the status attribute with validation
     */
    public function setStatusAttribute($value)
    {
        if (!in_array($value, self::statusValues())) {
            throw new \InvalidArgumentException("Invalid status value: {$value}. Must be one of: " . implode(', ', self::statusValues()));
        }

        $this->attributes['status'] = $value;
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
