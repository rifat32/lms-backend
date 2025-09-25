<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessRequest;
use App\Http\Requests\RegisterUserWithBusinessRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BusinessController extends Controller
{

    /**
     * @OA\Post(
     *     path="/v1.0/register-user-with-business",
     *     operationId="registerUserWithBusiness",
     *     tags={"Auth","business_management"},
     *     summary="Register a user and create a business",
     *     description="Create a new user and their business. Typically used by admin or self-registration depending on implementation.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="title", type="string", example="Mr"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="mdronymia040@gmail.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="12345678@We")
     *             ),
     *             @OA\Property(
     *                 property="business",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Learning Hub"),
     *                 @OA\Property(property="email", type="string", format="email", example="mdronymia040@gmail.com"),
     *                 @OA\Property(property="phone", type="string", example="+8801XXXXXXXXX"),
     *                 @OA\Property(property="registration_date", type="string", format="date", example="01-01-2010"),
     *                 @OA\Property(property="trail_end_date", type="string", format="date", example="01-02-2027"),
     *                 @OA\Property(property="about", type="string", example="About the business..."),
     *                 @OA\Property(property="web_page", type="string", example="https://example.com"),
     *                 @OA\Property(property="address_line_1", type="string", example="123 Example St"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="city", type="string", example="Dhaka"),
     *                 @OA\Property(property="postcode", type="string", example="1207"),
     *                 @OA\Property(property="currency", type="string", example="BDT"),
     *                 @OA\Property(property="logo", type="string", example="logos/acme.png")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User and business created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="business", type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Acme Ltd")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Invalid request"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="You do not have permission to perform this action."))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="user.email", type="array", @OA\Items(type="string", example="The user.email has already been taken.")),
     *                 @OA\Property(property="business.name", type="array", @OA\Items(type="string", example="The business.name field is required.")),
     *                 @OA\Property(property="business.registration_date", type="array", @OA\Items(type="string", example="The business.registration_date must be a valid date."))
     *             )
     *         )
     *     )
     * )
     */

    public function registerUserWithBusiness(RegisterUserWithBusinessRequest $request)
    {
        try {
            DB::beginTransaction();
            // if (!$request->user()->hasPermissionTo('business_create')) {
            //     return response()->json([
            //         "message" => "You can not perform this action"
            //     ], 401);
            // }

            // VALIDATE USER
            $request_payload = $request->validated();

            // PREPARE USER
            $request_payload['user']['created_by'] = auth()->user()->id;
            $request_payload['user']['password'] = Hash::make($request_payload['user']['password']);
            $request_payload['user']['remember_token'] = Str::random(10);
            // CREATE USER
            $user = User::create($request_payload['user']);

            // PREPARE BUSINESS
            $request_payload['business']['owner_id'] = $user->id;
            $request_payload['business']['created_by'] = $user->id;
            // CREATE BUSINESS
            $business = $user->business()->create($request_payload['business']);

            // UPDATE USER
            $user->assignRole('owner');
            $user->email_verified_at = now();
            $user->save();


            // PREPARE PREPARE RESPONSE 
            $user->business_id = $business->id;
            DB::commit();

            // SEND RESPONSE
            return response()->json([
                "success" => true,
                "message" => "Business Registration successfully",
                "data" => [
                    "user" => $user,
                    "business" => $business
                ]
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Post(
     *     path="/v1.0/businesses",
     *     operationId="createBusiness",
     *     tags={"business_management"},
     *     summary="Create a new business (Admin or Owner only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "registration_date", "address_line_1", "country", "city", "postcode"},
     *             @OA\Property(property="name", type="string", example="Acme Corporation"),
     *             @OA\Property(property="email", type="string", example="contact@acme.com"),
     *             @OA\Property(property="phone", type="string", example="+8801765432109"),
     *             @OA\Property(property="registration_date", type="string", format="date", example="2025-09-22"),
     *             @OA\Property(property="trail_end_date", type="string", format="date", example="2025-10-22"),
     *             @OA\Property(property="about", type="string", example="We provide tech solutions worldwide."),
     *             @OA\Property(property="web_page", type="string", example="https://acme.com"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Business Street"),
     *             @OA\Property(property="country", type="string", example="Bangladesh"),
     *             @OA\Property(property="city", type="string", example="Dhaka"),
     *             @OA\Property(property="postcode", type="string", example="1207"),
     *             @OA\Property(property="currency", type="string", example="BDT"),
     *             @OA\Property(property="logo", type="string", example="https://cdn.acme.com/logo.png")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Business created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Acme Corporation"),
     *                 @OA\Property(property="email", type="string", example="contact@acme.com"),
     *                 @OA\Property(property="phone", type="string", example="+8801765432109"),
     *                 @OA\Property(property="registration_date", type="string", format="date", example="2025-09-22"),
     *                 @OA\Property(property="trail_end_date", type="string", format="date", example="2025-10-22"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="city", type="string", example="Dhaka"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-22T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The name field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 ),
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email must be a valid email address.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function createBusiness(BusinessRequest $request)
    {
        try {
            DB::beginTransaction();

            $business = Business::create($request->validated());

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Business created successfully',
                'data' => $business
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Put(
     *     path="/v1.0/businesses",
     *     operationId="updateBusiness",
     *     tags={"business_management"},
     *     summary="Update an existing business (Admin or Owner only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "name", "registration_date", "address_line_1", "country", "city", "postcode"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Acme Corporation"),
     *             @OA\Property(property="email", type="string", example="contact@acme.com"),
     *             @OA\Property(property="phone", type="string", example="+8801765432109"),
     *             @OA\Property(property="registration_date", type="string", format="date", example="2025-09-22"),
     *             @OA\Property(property="trail_end_date", type="string", format="date", example="2025-10-22"),
     *             @OA\Property(property="about", type="string", example="We provide tech solutions worldwide."),
     *             @OA\Property(property="web_page", type="string", example="https://acme.com"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Business Street"),
     *             @OA\Property(property="country", type="string", example="Bangladesh"),
     *             @OA\Property(property="city", type="string", example="Dhaka"),
     *             @OA\Property(property="postcode", type="string", example="1207"),
     *             @OA\Property(property="currency", type="string", example="BDT"),
     *             @OA\Property(property="logo", type="string", example="https://cdn.acme.com/logo.png")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Acme Corporation"),
     *                 @OA\Property(property="email", type="string", example="contact@acme.com"),
     *                 @OA\Property(property="phone", type="string", example="+8801765432109"),
     *                 @OA\Property(property="registration_date", type="string", format="date", example="2025-09-22"),
     *                 @OA\Property(property="trail_end_date", type="string", format="date", example="2025-10-22"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="city", type="string", example="Dhaka"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-22T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The name field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 ),
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email must be a valid email address.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function updateBusiness(BusinessRequest $request)
    {
        try {
            DB::beginTransaction();
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // FIND BY ID
            $business = Business::findOrFail($request_payload['id']);
            // UPDATE
            $business->update($request_payload);

            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Business updated successfully',
                'data' => $business
            ], 200);
        } catch (\Throwable $th) {
            // ROLLBACK TRANSACTION
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Get(
     *     path="/v1.0/businesses/{id}",
     *     operationId="getBusinessById",
     *     tags={"business_management"},
     *     summary="Get a single business by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Business ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business fetched successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tech Corp Ltd"),
     *                 @OA\Property(property="email", type="string", example="info@techcorp.com"),
     *                 @OA\Property(property="phone", type="string", example="+880123456789"),
     *                 @OA\Property(property="registration_date", type="string", format="date", example="2025-09-22"),
     *                 @OA\Property(property="trail_end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="city", type="string", example="Dhaka"),
     *                 @OA\Property(property="postcode", type="string", example="1207"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-22T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Business not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong on the server.")
     *         )
     *     )
     * )
     */

    public function getBusinessById($id)
    {
        $business = Business::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Business fetched successfully',
            'data' => $business
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/businesses",
     *     operationId="getAllBusinesses",
     *     tags={"business_management"},
     *     summary="Get list of all businesses",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Businesses fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Businesses fetched successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Tech Corp Ltd"),
     *                     @OA\Property(property="email", type="string", example="info@techcorp.com"),
     *                     @OA\Property(property="phone", type="string", example="+880123456789"),
     *                     @OA\Property(property="registration_date", type="string", format="date", example="2025-09-22"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-22T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong on the server.")
     *         )
     *     )
     * )
     */

    public function getAllBusinesses(Request $request)
    {
        $businesses = Business::all();

        return response()->json([
            'success' => true,
            'message' => 'Businesses fetched successfully',
            'data' => $businesses
        ], 200);
    }
}
