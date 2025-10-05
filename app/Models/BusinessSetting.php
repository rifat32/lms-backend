<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;

           protected $fillable = [
        'STRIPE_KEY',
        "STRIPE_SECRET",
        'stripe_enabled',
    ];

       protected $hidden = [
        "STRIPE_SECRET"
    ];

}
