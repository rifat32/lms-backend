<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/v1.0/auth/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "first_name", "last_name", "email","password","role"},
     *             @OA\Property(property="title", type="string", example="Mr."),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@yopmail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="12345678@We"),
     *             @OA\Property(property="role", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Mr."),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="admin@yopmail.com"),
     *             @OA\Property(property="role", type="string", example="admin"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-17T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function register(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'title'     => 'required|string|max:255',
                'first_name'     => 'required|string|max:255',
                'last_name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                // 'business_id' => ['required', 'integer', new ValidBusiness()],
                'role'     => 'required|string|in:student,lecturer,admin',
            ]);

            $user = User::create([
                'title'     => $request->title,
                'first_name'     => $request->first_name,
                'last_name'     => $request->last_name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                // 'business_id' => $request->business_id
            ]);

            // $user->assignRole("$request->role" . "#" . $request->business_id);
            $user->assignRole($request->role);


            // Generate Passport token
            $token = $user->createToken('API Token')->accessToken;

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json(
                [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'data' => [
                        'user_id' => $user->id,
                        'title'    => $user->title,
                        'first_name'    => $user->first_name,
                        'last_name'    => $user->last_name,
                        'email'   => $user->email,
                        'role'    => $user->roles->pluck('name')->first(),
                        'token'   => $token,
                    ]
                ],
                201
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Post(
     *     path="/v1.0/auth/login",
     *     tags={"Auth"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@yopmail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="12345678@We")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Mr."),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="admin@yopmail.com"),
     *                 @OA\Property(property="role", type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     )
     * )
     */

    public function login(Request $request)
    {
        try {
            DB::beginTransaction();
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('API Token')->accessToken;

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user_id' => $user->id,
                    'title'    => $user->title,
                    'first_name'    => $user->first_name,
                    'last_name'    => $user->last_name,
                    'email'   => $user->email,
                    'role'    => $user->roles->pluck('name')->first(),
                    'token'   => $token,
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
