<?php

$superAdminPermissions = include(__DIR__ . '/permissions_super_admin.php');
$resellerPermissions = include(__DIR__ . '/permissions_reseller.php');
$businessOwnerPermissions = include(__DIR__ . '/permissions_business_owner.php');
$businessAdminPermissions = include(__DIR__ . '/permissions_business_admin.php');
$businessLecturerPermissions = include(__DIR__ . '/permissions_business_lecturer.php');
$businessStudentPermissions = include(__DIR__ . '/permissions_business_student.php');
return [

    "roles" => [
        'super_admin',
        'owner',
        'admin',
        'lecturer',
        'student'
    ],

    "system_modules" => [
        [
            "name" => "Login",
            "is_enabled" => true,
            "enabled_by_default" => false,
        ],
    ],

    "roles_permission" => [
        [
            "role" => "super_admin",
            "permissions" => $superAdminPermissions['permissions'],
        ],
        [
            "role" => "reseller",
            "permissions" => $resellerPermissions['permissions'],
        ],
        [
            "role" => "owner",
            "permissions" => $businessOwnerPermissions['permissions'],
        ],
        [
            "role" => "admin",
            "permissions" => $businessAdminPermissions['permissions'],
        ],
        [
            "role" => "lecturer",
            "permissions" => $businessLecturerPermissions['permissions'],
        ],
        [
            "role" => "student",
            "permissions" => $businessStudentPermissions['permissions'],
        ],
    ],

    "permissions" => [
        'super_admin',
        'reseller',
        'owner',
        'admin',
        'lecturer',
        'student',

        "user_create",
        "user_update",
        "user_view",
        "user_delete",

        "role_create",
        "role_update",
        "role_view",
        "role_delete",

        "business_create",
        "business_update",
        "business_view",
        "business_delete",
    ],

    "beautified_permissions_titles" => [],

    "beautified_permissions" => [
        [
            "header" => "user",
            "permissions" => [
                "user_create",
                "user_update",
                "user_view",
                "user_delete",

            ],
        ],
        [
            "header" => "role",
            "permissions" => [
                "role_create",
                "role_update",
                "role_view",
                "role_delete",

            ],
            "module" => "role"
        ],

        [
            "header" => "business",
            "permissions" => [
                "business_create",
                "business_update",
                "business_view",
                "business_delete",

            ],
        ],
    ],

];
