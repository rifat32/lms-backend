<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Utils\BasicUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Mail\StudentWelcomeMail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 */
class AuthController extends Controller
{
    use BasicUtil;

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
     *      path="/forgetpassword/reset/{token}",
     *      operationId="changePasswordByToken",
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
     *            required={"password"},
     *
     *     @OA\Property(property="password", type="string", format="string",* example="aaaaaaaa"),

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


    public function changePasswordByToken($token, Request $request)
    {
        // Validate inputs (no email-exists check to avoid enumeration)
        $data = $request->validate([
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'], // requires password_confirmation
        ]);

        // Fetch reset record for email
        $record = DB::table('password_resets')->where('email', $data['email'])->first();
        if (!$record) {
            return response()->json(['message' => 'Invalid or expired token.'], 400);
        }

        // Check token hash + expiry
        $validToken = Hash::check($token, $record->token);
        $minutes    = (int) config('auth.passwords.users.expire', 60);
        $expired    = Carbon::parse($record->created_at)->addMinutes($minutes)->isPast();

        if (!$validToken || $expired) {
            return response()->json(['message' => 'Invalid or expired token.'], 400);
        }

        // Load user (don’t leak details if missing)
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid or expired token.'], 400);
        }

        DB::beginTransaction();
        try {
            // Update password and reset lockout counters if present
            $user->password = Hash::make($data['password']);

            if (Schema::hasColumn($user->getTable(), 'login_attempts')) {
                $user->login_attempts = 0;
            }
            if (Schema::hasColumn($user->getTable(), 'last_failed_login_attempt_at')) {
                $user->last_failed_login_attempt_at = null;
            }
            if (Schema::hasColumn($user->getTable(), 'remember_token')) {
                $user->setRememberToken(Str::random(60));
            }

            $user->save();

            // Revoke existing OAuth tokens (if using Passport)
            if (Schema::hasTable('oauth_access_tokens')) {
                DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            }

            // Consume the reset token
            DB::table('password_resets')->where('email', $data['email'])->delete();

            DB::commit();
            return response()->json(['message' => 'Password changed'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
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
        // Validate format only (don’t leak existence)
        $data = $request->validate([
            'email'       => ['required', 'email'],
            'client_site' => ['nullable', 'url'], // ignored by your current Mailable; see note below
        ]);

        // Always return the same outward message
        $genericOk = response()->json([
            'message' => 'If an account exists for that address, we’ve sent reset instructions.',
        ], 200);

        // Look up user; if missing, return generic OK (no enumeration)
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }

        // Create raw+hashed token (store hash, email raw)
        $rawToken    = Str::random(64);
        $hashedToken = Hash::make($rawToken);

        try {
            DB::beginTransaction();

            // Upsert hashed token into password_resets
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token'      => $hashedToken,
                    'created_at' => Carbon::now(),
                ]
            );

            // Your ResetPasswordMail reads $user->resetPasswordToken for the URL.
            // Set it TEMPORARILY on the model instance (do NOT save to DB).
            $user->setAttribute('resetPasswordToken', $rawToken);

            // Send email (let exceptions signal failure; no Mail::failures()).
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
}
