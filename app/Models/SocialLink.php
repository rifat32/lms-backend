<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $table = 'social_links';
    protected $primaryKey = 'user_id'; // As discussed earlier
    public $incrementing = false;

    protected $hidden = ['pivot'];

    protected $fillable = [
        'user_id',
        'web_site',
        'facebook',
        'linkedin',
        'github',
        'twitter'
    ];
}
