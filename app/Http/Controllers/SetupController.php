<?php

namespace App\Http\Controllers;

use App\Utils\SetupUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

class SetupController extends Controller
{
    use SetupUtils;
    // initial setup
    public function setup()
    {
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Run fresh migrations
        Artisan::call('migrate:fresh', ['--force' => true]);

        // Re-install Passport (always, because migrate:fresh drops its tables)
        Artisan::call('migrate', ['--path' => 'vendor/laravel/passport/database/migrations']);
        Artisan::call('passport:install', ['--force' => true]);

        // Generate Swagger docs
        Artisan::call('optimize:clear');
        Artisan::call('l5-swagger:generate');

        // Seed database
        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

        return response()->json(['message' => 'Setup Complete']);
    }

    public function setupPassport()
    {
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Re-install Passport
        Artisan::call('migrate', ['--path' => 'vendor/laravel/passport/database/migrations']);
        Artisan::call('passport:install', ['--force' => true]);

        return response()->json(['message' => 'Passport Setup Complete']);
    }


    // SWAGGER REFRESH
    public function swaggerRefresh(Request $request)
    {

        Artisan::call('optimize:clear');
        Artisan::call('l5-swagger:generate');

        // Return a response
        return response()->json(['message' => 'Swagger Refreshed']);
    }

        public function migrate(Request $request)
    {
        Artisan::call('check:migrate');
        return "migrated";
    }


    // ROLE REFRESH
    public function roleRefresh(Request $request)
    {

        $this->rolesPermissionsRefresh();
        // Return a response
        return response()->json(['message' => 'Role Refreshed']);
    }
}
