<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Store a single uploaded file and optionally delete the previous filename.
     * Returns the stored filename (basename only).
     */
    private function putSingleFile(?UploadedFile $file, string $folderPath, ?string $oldFilename = null): ?string
    {
        if (!$file) {
            return $oldFilename;
        }

        $new = $file->hashName();
        $file->storeAs($folderPath, $new, 'public');

        if ($oldFilename) {
            $old = "{$folderPath}/{$oldFilename}";
            if (Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        return $new;
    }

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
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer', 'student'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $user = User::with(['roles:id,name', 'student_profile', 'social_links'])->find($id);
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->roles->pluck('name');
        // Hide pivot on each role before returning
        $user->roles->each->setHidden(['pivot']);

        return response()->json([
            'success' => true,
            'message' => 'User details retrieved successfully',
            'data' => $user
        ]);
    }

    /**
     * @OA\Put(
     *   path="/v1.0/users/{id}",
     *   operationId="updateUser",
     *   tags={"user_management"},
     *   summary="Update user profile (role: Admin only)",
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id", in="path", required=true, description="User ID",
     *     @OA\Schema(type="integer"), example=1
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Upload a file via multipart OR pass a string path via JSON, both using the same key: profile_photo.",
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(property="title", type="string", example="Mr."),
     *         @OA\Property(property="first_name", type="string", example="John"),
     *         @OA\Property(property="last_name", type="string", example="Doe"),
     *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *         @OA\Property(property="phone", type="string", example="+1 555 123 4567"),
     *
     *         @OA\Property(
     *           property="profile_photo",
     *           type="string",
     *           format="binary",
     *           description="Image file (jpg, jpeg, png, webp, avif, gif), max 5MB"
     *         )
     *       )
     *     ),
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"id"},
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="title", type="string", example="Mr."),
     *         @OA\Property(property="first_name", type="string", example="John"),
     *         @OA\Property(property="last_name", type="string", example="Doe"),
     *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *         @OA\Property(property="phone", type="string", example="+1 555 123 4567"),
     *
     *         @OA\Property(
     *           property="profile_photo",
     *           type="string",
     *           example="https://cdn.example.com/avatars/john.jpg",
     *           description="Alternative to file upload: provide an existing path/URL; server stores the basename under users/{id}/"
     *         )
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="User profile updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="User profile updated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="title", type="string", example="Mr."),
     *         @OA\Property(property="first_name", type="string", example="John"),
     *         @OA\Property(property="last_name", type="string", example="Doe"),
     *         @OA\Property(property="email", type="string", example="john@example.com"),
     *         @OA\Property(property="role", type="string", example="student"),
     *
     *         @OA\Property(
     *           property="profile_photo",
     *           type="string",
     *           example="users/1/2a3b4c5d6e7f.jpg",
     *           description="Stored relative path in DB (column: profile_photo)"
     *         ),
     *         @OA\Property(
     *           property="profile_photo_url",
     *           type="string",
     *           example="https://static.example.com/storage/users/1/2a3b4c5d6e7f.jpg",
     *           description="Public URL derived from storage (accessor)"
     *         )
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(response=400, description="Bad request",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="Invalid request payload"))
     *   ),
     *   @OA\Response(response=401, description="Unauthorized",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized access"))
     *   ),
     *   @OA\Response(response=403, description="Forbidden",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="You do not have permission to update this user"))
     *   ),
     *   @OA\Response(response=404, description="User not found",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="User not found"))
     *   ),
     *   @OA\Response(response=409, description="Conflict",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="Email is already in use"))
     *   ),
     *   @OA\Response(response=422, description="Validation error",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="The email field must be a valid email address"))
     *   ),
     *   @OA\Response(response=500, description="Internal server error",
     *     @OA\JsonContent(@OA\Property(property="message", type="string", example="An unexpected error occurred while updating the user profile"))
     *   )
     * )
     */



    public function updateUser(UserUpdateRequest $request, $id)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer', 'student'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // Start a database transaction
            DB::beginTransaction();

            // Validate the request payload
            $request_payload = $request->validated();

            $user = User::find($id);

            if (empty($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $folder_path = "business_1/profile_photo_{$user->id}";
            if ($request->hasFile('profile_photo')) {
                $photo_filename = $this->putSingleFile(
                    $request->file('profile_photo'),
                    $folder_path,
                    $user->getRawOriginal('profile_photo')
                );
                $request_payload['profile_photo'] = $photo_filename;
            } elseif ($request->filled('profile_photo') && is_string($request->input('profile_photo'))) {
                $request_payload['profile_photo'] = basename($request->input('profile_photo'));
            } else {
                // Remove from update data if not provided (keep existing)
                unset($request_payload['profile_photo']);
            }

            $user->update($request_payload);

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'User profile updated successfully',
                'data' => $user
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
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $query = User::with(['roles', 'enrollments', 'student_profile', 'social_links'])
            ->filter();

        $users = retrieve_data($query, 'created_at', 'users');



        $summary = [];

        $summary["total_users"] =   User::whereHas('roles', function ($q) {
            $q->where('roles.name', '!=', 'super_admin');
        })->whereHas('roles', function ($q) {
            $q->where('roles.name', '!=', 'owner');
        })->get()->count();
        $summary["total_students"] =   User::whereHas('roles', function ($q) {
            $q->where('roles.name', 'student');
        })->count();

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            "summary" => $summary,
            'meta' => $users['meta'],
            'data' => $users['data'],
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
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
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
