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
use Illuminate\Support\Facades\Mail;

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
                 'business_id' => $request->business_id??1
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
     *      tags={"auth"},
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
        DB::beginTransaction();
        try {
   $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
            $request_data = $request->all();
            $user = User::where([
                "resetPasswordToken" => $token,
            ])
                ->where("resetPasswordExpires", ">", now())
                ->first();
            if (!$user) {

                return response()->json([
                    "message" => "Invalid Token Or Token Expired"
                ], 400);
            }

            $password = Hash::make($request_data["password"]);
            $user->password = $password;

            $user->login_attempts = 0;
            $user->last_failed_login_attempt_at = null;


            $user->save();

            DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->delete();

            DB::commit();
            return response()->json([
                "message" => "password changed"
            ], 200);
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
     *      tags={"auth"},

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

        DB::beginTransaction();
        try {

             $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

                $request_data = $request->all();

            $user = User::where(["email" => $request_data["email"]])->first();
            if (!$user) {

                return response()->json(["message" => "no user found"], 404);
            }

            $token = Str::random(30);

            $user->resetPasswordToken = $token;
            $user->resetPasswordExpires = Carbon::now()->subDays(-1);
            $user->save();



            if (env("SEND_EMAIL") == true) {
            

                try {
                    $result = Mail::to($request_data["email"])->send(new ResetPasswordMail($user, $request_data["client_site"]));
                } catch (\Exception $e) {
                    // Optionally log the error message if needed
                    Log::error("Failed to send email: " . $e->getMessage());
                    // Continue processing without interrupting the flow
                }

            }

            if (count(Mail::failures()) > 0) {
                // Handle failed recipients and log the error messages
                foreach (Mail::failures() as $emailFailure) {
                }
                throw new Exception("Failed to send email to:" . $emailFailure);
            }

            DB::commit();
            return response()->json([
                "message" => "Please check your email."
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->sendError($e);
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
