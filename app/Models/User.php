<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'profile_photo',
        'email_verified_at',
        'business_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pivot'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getFullNameAttribute(): string
    {
        $name = collect([$this->title, $this->first_name, $this->last_name])
            ->filter()              // drop null/empty parts
            ->implode(' ');         // "Mr. John Doe"

        return $name !== '' ? $name : ($this->email ?? 'User');
    }


    // Single file accessors (your existing code)
    public function getProfilePhotoAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $folder_path = "business_1/profile_photo_{$this->id}";
        return asset("storage-proxy/{$folder_path}/{$value}");
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }


    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_id', 'id');
    }
}
