<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

class SetupController extends Controller
{

    // initial setup
    public function setup()
    {
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Run migrations normally
        Artisan::call('migrate:refresh');

        // Register Passport routes (safe call)
        Passport::routes();

        // Install passport only if not already installed (first time only)
        Artisan::call('passport:install');
        Artisan::call('passport:keys');

        // Generate swagger docs
        Artisan::call('l5-swagger:generate');

        // GET ALL ROLES
        $roles = config('setup-config.roles');

        // OF NOT EXIST THEN CREATE NEW
        foreach ($roles as $role) {
            if (!Role::where('name', $role)->where('guard_name', 'api')->exists())
                Role::create(
                    ['name' => $role, 'guard_name' => 'api']
                );
        }

        return response()->json(['message' => 'Setup Complete']);
    }


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
            if (!Role::where('name', $role)->where('guard_name', 'api')->exists())
                Role::create(
                    ['name' => $role, 'guard_name' => 'api']
                );
        }

        // Return a response
        return response()->json(['message' => 'Role Refreshed']);
    }
}
