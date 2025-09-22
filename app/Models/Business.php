<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'registration_date',
        'trail_end_date',
        'about',
        'web_page',
        'additional_information',
        'address_line_1',
        'country',
        'city',
        'postcode',
        'currency',
        'service_plan_id',
        'status',
        'is_active',
        'logo',
        'owner_id',
        'created_by',
    ];
}
