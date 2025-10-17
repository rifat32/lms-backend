<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {

            // Create super admin user
            $superAdmin = User::firstOrCreate(
                ['email' => 'asjadtariq@gmail.com'],
                [
                    'title' => 'Mr.',
                    'first_Name' => 'super',
                    'last_Name' => 'admin',
                    'password' => Hash::make('12345678@We'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign super_admin role
            $role = Role::where('name', 'super_admin')->first();
            if ($role && !$superAdmin->hasRole($role)) {
                $superAdmin->assignRole($role);
            }
   
    }
}
