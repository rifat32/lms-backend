<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Endpoints to view and update user profile"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/users/{id}",
     *     operationId="getUserById",
     *     tags={"Users"},
     *     summary="Get user details by ID",
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
     *     tags={"Users"},
     *     summary="Update user profile",
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
    }
}
