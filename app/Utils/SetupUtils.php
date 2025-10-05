<?php

namespace App\Utils;

use App\Models\Business;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

trait SetupUtils
{


    public function rolesPermissionsRefresh()
    {
        DB::transaction(function () {

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
            $businessRoles = array_filter($roles, fn($role) => $role !== "super_admin");

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
                        'is_default_for_business' => in_array($roleName, $businessRoles) ? 1 : 0
                    ]
                );
            }

            // ###############################
            // ROLES & PERMISSIONS (System Roles)
            // ###############################
            $rolePermissionsConfig = config('setup-config.roles_permission', []);
            foreach ($rolePermissionsConfig as $rolePerm) {
                $role = Role::where('name', $rolePerm['role'])->first();
                if ($role) {
                    $role->syncPermissions($rolePerm['permissions']);
                }
            }

            // ###############################
            // BUSINESS-SPECIFIC ROLES
            // ###############################
            $businessIds = Business::pluck('id');

            foreach ($rolePermissionsConfig as $rolePermission) {
                foreach ($businessIds as $businessId) {
                    $roleName = $rolePermission['role'] . "#" . $businessId;
                    $role = Role::where('name', $roleName)->first();

                    if (!$role) continue;

                    $newPermissions = $rolePermission['permissions'];
                    $currentPermissions = $role->permissions()->pluck('name')->toArray();
                    $permissionsToAdd = array_diff($newPermissions, $currentPermissions);

                    if (!empty($permissionsToAdd)) {
                        $role->givePermissionTo($permissionsToAdd);
                    }
                }
            }

            // Clear Spatie permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }




}
