<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class SetupController extends Controller
{
    // SWAGGER REFRESH
    public function swaggerRefresh(Request $request)
    {

        Artisan::call('optimize:clear');
        Artisan::call('l5-swagger:generate');

        // Return a response
        return response()->json(['message' => 'Swagger Refreshed']);
    }


    // ROLE REFRESH
    public function roleRefresh(Request $request)
    {

        // GET ALL ROLES
        $roles = config('setup-config.roles');

        // OF NOT EXIST THEN CREATE NEW
        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role, 'guard_name' => 'api']
            );
        }

        // Return a response
        return response()->json(['message' => 'Role Refreshed']);
    }
}
