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

    protected $fillable = [
        'name',
        'email',
        'phone',
        'registration_date',
        'about',
        'web_page',
        'additional_information',
        'address_line_1',
        'country',
        'city',
        'postcode',
        'currency',
        'status',
        'is_active',
        'logo',
        'owner_id',
        'created_by',
    ];

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
