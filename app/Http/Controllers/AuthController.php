<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordWithTokenRequest;
use App\Http\Requests\VerifyBusinessEmailRequest;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Utils\BasicUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Mail\StudentWelcomeMail;
use App\Models\Business;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 */
class AuthController extends Controller
{
    use BasicUtil;

    // Helper method to create signed token
    private function createSignedToken(string $email): string
    {
        $payload = [
            'email' => $email,
            'random' => Str::random(32), // Randomness for uniqueness
            'expires_at' => now()->addMinutes(5)->timestamp, // expire after 5 minutes
        ];

        $encoded = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $encoded, config('app.key'));

        return "{$encoded}.{$signature}";
    }

    // Helper method to verify and decode token
    private function verifySignedToken(string $token): ?array
    {
        try {
            [$encoded, $signature] = explode('.', $token, 2);

            // Verify signature
            $expectedSignature = hash_hmac('sha256', $encoded, config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                throw ValidationException::withMessages([
                    'token' => 'Reset token is invalid or has been tampered with.',
                ]);
            }

            // Decode payload
            $payload = json_decode(base64_decode($encoded), true);

            // Check expiry
            if ($payload['expires_at'] < now()->timestamp) {
                throw ValidationException::withMessages([
                    'token' => 'Reset link has expired. Please request a new one.',
                ]);
            }

            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @OA\Post(
     *     path="/v1.0/auth/register",
     *     tags={"Auth"},
     *     summary="Register a new user (role: Any Role)",
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
                'business_id' => $request->business_id ?? 1
            ]);

            // $user->assignRole("$request->role" . "#" . $request->business_id);
            $user->assignRole($request->role);


            // Generate Passport token
            $token = $user->createToken('API Token')->accessToken;

            $business_settings = $this->get_business_setting();

            if (env("SEND_EMAIL") == true) {
                if ($request->role === 'student') {
                    Mail::to($user->email)->send(new StudentWelcomeMail($user));
                }
            }


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
                        'business' => $business_settings
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
     *
     * @OA\Patch(
     *      path="/reset-password/{token}",
     *      operationId="resetPasswordWithToken",
     *      tags={"Auth"},
     *  @OA\Parameter(
     * name="token",
     * in="path",
     * description="token",
     * required=true,
     * example="1"
     * ),
     *      summary="This method is to change password",
     *      description="This method is to change password",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"password","password_confirmation"},
     *
     *     @OA\Property(property="password", type="string", format="string",* example="aaaaaaaa"),
     *     @OA\Property(property="password_confirmation", type="string", format="string",* example="aaaaaaaa"),

     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */


    public function resetPasswordWithToken(ChangePasswordWithTokenRequest $request, string $token = "")
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            // 1. Verify and decode the signed token
            $payload = $this->verifySignedToken($token);
            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token. Please request a new one.'
                ], 400);
            }

            $email = $payload['email']; // ✅ Extract email from token

            // 2. Check if token exists in database
            $resetRecord = DB::table('password_resets')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found. Please request a new password reset.'
                ], 400);
            }

            // 3. Find user and update password
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            $user->password = Hash::make($data['password']);
            $user->save();

            // 6. Delete used token
            DB::table('password_resets')->where('email', $email)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Password has been reset successfully.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/forgetpassword",
     *      operationId="storeToken",
     *      tags={"Auth"},

     *      summary="This method is to store token",
     *      description="This method is to store token",

     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",* example="test@g.c"),

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */



    public function storeToken(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'client_site' => ['nullable', 'url'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            // Return generic message (prevent email enumeration)
            return response()->json([
                'message' => 'If an account exists for that address, we have sent reset instructions.',
            ], 200);
        }

        try {
            DB::beginTransaction();

            // Create signed token with email embedded
            $signedToken = $this->createSignedToken($user->email);

            // Hash for storage (additional security layer)
            $hashedToken = Hash::make($signedToken);
            $expiresAt = now()->addHour(); // 1 hour expiry

            // Store hashed token
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                ]
            );

            // Send raw signed token in email (not hashed)
            $user->setAttribute('resetPasswordToken', $signedToken);

            if (env("SEND_EMAIL") == true) {
                Mail::to($user->email)->send(new ResetPasswordMail($user));
            }

            DB::commit();

            return response()->json([
                "message" => "Please check your email."
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }



    /**
     * @OA\Post(
     *     path="/v1.0/auth/login",
     *     tags={"Auth"},
     *     summary="Login user (role: Any Role)",
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

            $business_settings = $this->get_business_setting();
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
                    'business' => $business_settings
                ]
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/auth/verify-user-email",
     *      operationId="verifyUserEmail",
     *      tags={"Auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check user",
     *      description="This method is to check user",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="string",example="test@g.c"),
     *             @OA\Property(property="user_id", type="integer", format="int64",example="1"),
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */


    public function verifyUserEmail(Request $request)
    {
        try {

            // Validate the request
            $payload_data = $request->validate([
                'email' => 'required|email',
                'user_id' => 'nullable|integer|exists:users,id'
            ]);

            $user = User::where('email', $payload_data['email'])
                ->when(!empty($payload_data['user_id']), function ($query) use ($payload_data) {
                    $query->where('id', '!=', $payload_data['user_id']);
                })
                ->select('id')
                ->first();

            if ($user) {
                return response()->json([
                    "data" => true,
                    "message" => "Email already exists",
                ], 409); // Changed to 409 Conflict for better semantics
            }

            return response()->json([
                "data" => false,
                "message" => "Email is available",
            ], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/auth/verify-business-email",
     *      operationId="verifyBusinessEmail",
     *      tags={"Auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="Verify business email availability",
     *      description="Check if a business email is available, excluding the specified business_id if provided.",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="business@example.com"),
     *             @OA\Property(property="business_id", type="integer", format="int64", example=1),
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Email is available",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Email is available")
     *          )
     *       ),
     *      @OA\Response(
     *          response=409,
     *          description="Email already exists",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Email already exists")
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *@OA\JsonContent()
     *      )
     *     )
     */

    public function verifyBusinessEmail(VerifyBusinessEmailRequest $request)
    {
        try {
            $payload_data = $request->validated();

            $business = Business::where('email', $payload_data['email'])
                ->when(!empty($payload_data['business_id']), function ($query) use ($payload_data) {
                    $query->where('id', '!=', $payload_data['business_id']);
                })
                ->select('id')
                ->first();

            if ($business) {
                return response()->json([
                    "data" => true,
                    "message" => "Email already exists",
                ], 409); // Conflict for existing email
            }

            return response()->json([
                "data" => false,
                "message" => "Email is available",
            ], 200);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/verify-user-by-token",
     *      operationId="verifyUserByToken",
     *      tags={"Auth"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get  user by token",
     *      description="This method is to get user",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */


    public function verifyUserByToken(Request $request)
    {
        try {

            $user = $request->user();
            $user->permissions = $user->getAllPermissions()->pluck('name');
            $user->roles = $user->roles->pluck('name');
            $user->business = $user->business;

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'user' => $user
            ], 200);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
