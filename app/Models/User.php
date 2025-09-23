<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @OA\Schema(
 *     schema="UserSchema",
 *     type="object",
 *     title="User",
 *     required={"id", "first_name", "last_name", "email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Mr."),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *     @OA\Property(property="business_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-23T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-23T12:00:00Z")
 * )
 */


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'email',
        'password',
        'email_verified_at',
        'business_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
