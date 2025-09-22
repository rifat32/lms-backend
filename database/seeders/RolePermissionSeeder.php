<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $this->setupRolesAndPermissions();
    }

    private function setupRolesAndPermissions()
    {
        // ###############################
        // PERMISSIONS
        // ###############################
        $permissions = config('setup-config.permissions', []);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        // ###############################
        // ROLES
        // ###############################
        $roles = config('setup-config.roles', []);
        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'api',
                    'is_system_default' => 1,
                    'business_id' => null,
                    'is_default' => 1,
                ],
                [
                    'is_default_for_business' => in_array($roleName, [
                        'owner',
                        'admin',
                        'lecturer',
                        'student',
                    ]) ? 1 : 0
                ]
            );
        }

        // ###############################
        // ROLES & PERMISSIONS
        // ###############################
        $rolePermissions = config('setup-config.roles_permission', []);
        foreach ($rolePermissions as $rolePerm) {
            $role = Role::where('name', $rolePerm['role'])->first();
            if ($role) {
                $role->syncPermissions($rolePerm['permissions']);
            }
        }
    }
}
