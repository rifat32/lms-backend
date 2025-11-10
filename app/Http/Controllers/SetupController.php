<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Utils\SetupUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SetupController extends Controller
{
    use SetupUtils;

    public function cacheClear()
    {
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $user = User::where('email', 'admin@yopmail.com')->first();
        $user->update([
            'email' => 'admin.collegeemail@yopmail.com',
        ]);


        return response()->json(['message' => 'Caches cleared']);
    }


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
    public function rollbackMigration(Request $request)
    {
        try {
            $result = Artisan::call('migrate:rollback');

            return response()->json([
                'message' => 'Last Migration Rolled Back',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            // LOG ERROR MESSAGE
            log_message([
                'message' => 'Migration Roll Back Failed',
                'data' => $e->getMessage()
            ], 'roll_back.log');

            return response()->json([
                'message' => 'Last Migration Rolled Back',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storageLink(Request $request)
    {
        Artisan::call('storage:link');
        return "storage linked";
    }


    // ROLE REFRESH
    public function roleRefresh(Request $request)
    {

        $this->rolesPermissionsRefresh();
        // Return a response
        return response()->json(['message' => 'Role Refreshed']);
    }
}
