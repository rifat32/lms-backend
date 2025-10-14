<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $hidden = ['pivot'];
    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'guard_name',
        'business_id',
        'is_default',
        "is_system_default",
        "is_default_for_business",

    ];
}
