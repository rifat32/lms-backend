<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ['pivot'];

    /**
     * Machine value => Human label
     * (Keep keys EXACTLY as stored in DB.)
     */
    public const STATUS_OPTIONS = [
        'Pending' => 'pending',
        'Active' => 'active',
        'Suspended' => 'suspended',
        'Cancelled' => 'cancelled',
        'Expired' => 'expired',
        'Trial_Ended' => 'trail_ended',
        'Inactive' => 'inactive',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'registration_date',

        'about',
        'web_page',
        'address_line_1',
        'address_line_2',
        'country',
        'city',
        'postcode',
        'currency',
        'latitude',
        'longitude',
        'logo',
        'image',
        'background_image',
        'theme',
        'images',
        'additional_information',

        'status',
        'is_active',
        'owner_id',
        'created_by',
    ];

    protected $casts = [
        // 'registration_date' => 'date',
        'images'            => 'array',
        'is_active'         => 'boolean',
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

    // Single file accessors (your existing code)
    public function getLogoAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $folder_path = "business_1/business_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }

    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $folder_path = "business_1/business_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }

    public function getBackgroundImageAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $folder_path = "business_1/business_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }

    // Multiple files accessor for JSON field
    public function getImagesAttribute($value)
    {
        // Cast handles JSON decode automatically
        $images = $this->castAttribute('images', $value);

        if (empty($images) || !is_array($images)) {
            return [];
        }

        $folder_path = "business_1/business_{$this->id}";

        return array_map(function ($filename) use ($folder_path) {
            return empty($filename) ? null : asset("storage-proxy/{$folder_path}/{$filename}");
        }, $images);
    }


    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'business_id');
    }
}
