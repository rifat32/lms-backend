<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/users/{id}",
     *     operationId="getUserById",
     *     tags={"user_management"},
     *     summary="Get user details by ID (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Mr."),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="role", type="string", example="student")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid ID format",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid user ID format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while retrieving user details")
     *         )
     *     )
     * )
     */

    public function getUserById($id)
    {
        if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $query = User::find($id);
        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user = [
            'id'            => $query->id,
            'title'          => $query->title,
            'first_name'          => $query->first_name,
            'last_name'          => $query->last_name,
            'email'         => $query->email,
            'role'          => $query->roles->pluck('name')->first(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'User details retrieved successfully',
            'data' => $user
        ]);
    }


    /**
     * @OA\Put(
     *     path="/v1.0/users",
     *     operationId="updateUser",
     *     tags={"user_management"},
     *     summary="Update user profile (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "title", "first_name", "last_name", "email"},
     *             @OA\Property(property="id", type="integer", example="1"),
     *             @OA\Property(property="title", type="string", example="Mr."),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Mr."),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="role", type="string", example="student")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request payload")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update this user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Email already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email is already in use")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field must be a valid email address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while updating the user profile")
     *         )
     *     )
     * )
     */

    public function updateUser(UserUpdateRequest $request)
    {
        try {
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            // Start a database transaction
            DB::beginTransaction();

            // Validate the request payload
            $request_payload = $request->validated();

            $user = User::find($request_payload['id']);

            if (empty($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($request->filled('password')) {
                $request_payload['password'] = Hash::make($request->password);
            }

            $user->update($request_payload);

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'User profile updated successfully',
                'data' => [
                    'id'            => $user->id,
                    'title'          => $user->title,
                    'first_name'          => $user->first_name,
                    'last_name'          => $user->last_name,
                    'email'         => $user->email,
                    'role'          => $user->roles->pluck('name')->first(),
                ]
            ]);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Get(
     *     path="/v1.0/users",
     *     operationId="getAllUsers",
     *     tags={"user_management"},
     *     summary="Get all users (role: Admin only)",
     *     description="Retrieve a list of all users in the system",
     *     security={{"bearerAuth":{}}},
     * *     @OA\Parameter(
 *         name="role",
 *         in="query",
 *         required=false,
 *         description="Filter users by role name (e.g., admin, student, lecturer)",
 *         @OA\Schema(type="string", example="student")
 *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     title="User",
     *                     required={"id", "first_name", "last_name", "email"},
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Mr."),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="business_id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-23T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-23T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid request parameters")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Resource not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */

    public function getAllUsers()
    {
        if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $query = User::query();

         // ROLE FILTER (Spatie relationship-based)
    if (request()->filled('role')) {
        $query->whereHas('roles', function ($q)  {
            $q->where('roles.name', request()->role);
        });
    }

        $users = retrieve_data($query, 'created_at', 'users');



        $summary = [];

    $summary["total_users"] =   User::get()->count();
     $summary["total_students"] =   User::whereHas('roles', function ($q)  {
            $q->where('roles.name', 'student');
        })->count();

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'meta' => $users['meta'],
            'data' => $users['data'],
            "summary" => $summary
        ], 200);


    }

    /**
     * @OA\Delete(
     *     path="/v1.0/users/{ids}",
     *     operationId="deleteUsers",
     *     tags={"user_management"},
     *     summary="Delete users (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="User ID (comma-separated for multiple)",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson not found")
     *         )
     *     )
     * )
     */
    public function deleteUsers($ids)
    {
        try {
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            DB::beginTransaction();

            // Validate and convert comma-separated IDs to an array
            $idsArray = array_map('intval', explode(',', $ids));

            // Get existing users
            $users = User::whereIn('id', $idsArray)->get();

            $existingIds = $users->pluck('id')->toArray();

            if (count($existingIds) !== count($idsArray)) {
                $missingIds = array_diff($idsArray, $existingIds);

                return response()->json([
                    'success' => false,
                    'message' => 'users not found',
                    'data' => [
                        'missing_ids' => array_values($missingIds)
                    ]
                ], 400);
            }


            // Delete users
            User::whereIn('id', $existingIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
