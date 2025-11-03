<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessUpdateRequest;
use App\Http\Requests\RegisterUserWithBusinessRequest;
use App\Models\Business;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BusinessController extends Controller
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
     * @OA\Post(
     *     path="/v1.0/register-user-with-business",
     *     operationId="registerUserWithBusiness",
     *     tags={"Auth","business_management"},
     *     summary="Register a user and create a business (role: Super Admin Only)",
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


            if (!auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


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
     * @OA\Put(
     *     path="/v1.0/businesses",
     *     operationId="updateBusiness",
     *     tags={"business_management"},
     *     summary="Update an existing business (role: Super Admin, owner, admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "country", "city", "address_line_1"},
     *             @OA\Property(property="id", type="integer", example=1, description="Business ID (must exist in businesses table)"),
     *             @OA\Property(property="name", type="string", example="Acme Corporation", description="Business name"),
     *             @OA\Property(property="about", type="string", example="We provide tech solutions worldwide.", description="About the business"),
     *             @OA\Property(property="web_page", type="string", example="https://acme.com", description="Website URL"),
     *             @OA\Property(property="pin_code", type="string", example="1207", description="Optional pin code"),
     *             @OA\Property(property="phone", type="string", example="+8801765432109", description="Business phone number"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@acme.com", description="Business email (must be unique)"),
     *             @OA\Property(property="additional_information", type="string", example="24/7 support available", description="Additional information"),
     *             @OA\Property(property="lat", type="number", format="float", example=23.7808875, description="Latitude coordinate"),
     *             @OA\Property(property="long", type="number", format="float", example=90.2792371, description="Longitude coordinate"),
     *             @OA\Property(property="currency", type="string", example="BDT", description="Currency code (e.g., USD, BDT, EUR)"),
     *             @OA\Property(property="country", type="string", example="Bangladesh", description="Country name"),
     *             @OA\Property(property="city", type="string", example="Dhaka", description="City name"),
     *             @OA\Property(property="postcode", type="string", example="1207", description="Postal/ZIP code"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Business Street", description="Primary address line"),
     *             @OA\Property(property="address_line_2", type="string", example="2nd Floor, Suite 5", description="Secondary address line (optional)"),
     *             @OA\Property(property="theme", type="string", example="dark", description="UI theme preference"),
     *             @OA\Property(property="logo", type="string", example="https://cdn.acme.com/logo.png", description="Logo URL or file upload"),
     *             @OA\Property(property="image", type="string", example="https://cdn.acme.com/cover.jpg", description="Cover image URL or file upload"),
     *             @OA\Property(property="background_image", type="string", example="https://cdn.acme.com/bg.jpg", description="Background image URL or file upload"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 description="Gallery images (URLs or file uploads)",
     *                 @OA\Items(type="string", example="https://cdn.acme.com/gallery/img1.jpg")
     *             )
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
     *                 @OA\Property(property="about", type="string", example="We provide tech solutions worldwide."),
     *                 @OA\Property(property="web_page", type="string", example="https://acme.com"),
     *                 @OA\Property(property="pin_code", type="string", example="1207"),
     *                 @OA\Property(property="phone", type="string", example="+8801765432109"),
     *                 @OA\Property(property="email", type="string", example="contact@acme.com"),
     *                 @OA\Property(property="additional_information", type="string", example="24/7 support available"),
     *                 @OA\Property(property="lat", type="number", example=23.7808875),
     *                 @OA\Property(property="long", type="number", example=90.2792371),
     *                 @OA\Property(property="currency", type="string", example="BDT"),
     *                 @OA\Property(property="country", type="string", example="Bangladesh"),
     *                 @OA\Property(property="city", type="string", example="Dhaka"),
     *                 @OA\Property(property="postcode", type="string", example="1207"),
     *                 @OA\Property(property="address_line_1", type="string", example="123 Business Street"),
     *                 @OA\Property(property="address_line_2", type="string", example="2nd Floor, Suite 5"),
     *                 @OA\Property(property="theme", type="string", example="dark"),
     *                 @OA\Property(property="logo", type="string", example="http://yourapp.com/storage-proxy/business_1/business_1/abc123def456.png"),
     *                 @OA\Property(property="image", type="string", example="http://yourapp.com/storage-proxy/business_1/business_1/def456ghi789.jpg"),
     *                 @OA\Property(property="background_image", type="string", example="http://yourapp.com/storage-proxy/business_1/business_1/ghi789jkl012.jpg"),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(type="string", example="http://yourapp.com/storage-proxy/business_1/business_1/jkl012mno345.jpg")
     *                 ),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-11-03T15:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Business ID is required"))
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
     *         response=404,
     *         description="Business not found",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="No business found with this id"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The id field is required. (and 3 more errors)"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="id", type="array", @OA\Items(type="string", example="The business ID field is required.")),
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field may not be greater than 255 characters.")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken.")),
     *                 @OA\Property(property="web_page", type="array", @OA\Items(type="string", example="The web page must be a valid URL.")),
     *                 @OA\Property(property="lat", type="array", @OA\Items(type="string", example="The latitude must be a number.")),
     *                 @OA\Property(property="long", type="array", @OA\Items(type="string", example="The longitude must be a number.")),
     *                 @OA\Property(property="country", type="array", @OA\Items(type="string", example="The country field is required.")),
     *                 @OA\Property(property="city", type="array", @OA\Items(type="string", example="The city field is required.")),
     *                 @OA\Property(property="address_line_1", type="array", @OA\Items(type="string", example="The address line 1 field is required.")),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string", example="The images field must be an array.")),
     *                 @OA\Property(property="images.0", type="array", @OA\Items(type="string", example="Each image must be an image file (jpg, jpeg, png, gif, webp)."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="An error occurred while updating the business."))
     *     )
     * )
     */


    public function businessOwnerCheck($business_id)
    {

        $business = Business::where('id', $business_id)
            ->when(
                !request()->user()->hasAnyRole(['owner', 'admin', 'super_admin']),
                function ($query) {
                    $query->where(function ($query) {
                        $query

                            ->where('owner_id', auth()->user()->id);
                    });
                },
            )
            ->first();


        if (empty($business)) {
            throw new Exception("you are not the owner of the business or the requested business does not exist.", 401);
        }
        return $business;
    }



    public function updateBusiness(BusinessUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $request_data = $request->validated();

            // Validate business ID exists
            if (empty($request_data['id'])) {
                throw new Exception("Business ID is required", 400);
            }

            $business = $this->businessOwnerCheck($request_data["id"]);
            $folder_path = "business_1/business_{$business->id}";

            // ========================
            // HANDLE LOGO
            // ========================
            if ($request->hasFile('logo')) {
                $logo_filename = $this->putSingleFile(
                    $request->file('logo'),
                    $folder_path,
                    $business->getRawOriginal('logo')
                );
                $request_data['logo'] = $logo_filename;
            } elseif ($request->filled('logo') && is_string($request->input('logo'))) {
                $request_data['logo'] = basename($request->input('logo'));
            } else {
                // Remove from update data if not provided (keep existing)
                unset($request_data['logo']);
            }

            // ========================
            // HANDLE IMAGE
            // ========================
            if ($request->hasFile('image')) {
                $imageFilename = $this->putSingleFile(
                    $request->file('image'),
                    $folder_path,
                    $business->getRawOriginal('image')
                );
                $request_data['image'] = $imageFilename;
            } elseif ($request->filled('image') && is_string($request->input('image'))) {
                $request_data['image'] = basename($request->input('image'));
            } else {
                unset($request_data['image']);
            }

            // ========================
            // HANDLE BACKGROUND IMAGE
            // ========================
            if ($request->hasFile('background_image')) {
                $bgFilename = $this->putSingleFile(
                    $request->file('background_image'),
                    $folder_path,
                    $business->getRawOriginal('background_image')
                );
                $request_data['background_image'] = $bgFilename;
            } elseif ($request->filled('background_image') && is_string($request->input('background_image'))) {
                $request_data['background_image'] = basename($request->input('background_image'));
            } else {
                unset($request_data['background_image']);
            }

            // ========================
            // HANDLE IMAGES (MULTIPLE)
            // ========================
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $file) {
                    $images[] = $this->putSingleFile($file, $folder_path, null);
                }
                $request_data['images'] = $images;
            } elseif ($request->filled('images') && is_array($request->input('images'))) {
                $images = [];
                foreach ($request->input('images') as $image) {
                    if (is_string($image) && $image !== '') {
                        $images[] = basename($image);
                    }
                }
                $request_data['images'] = $images;
            } else {
                unset($request_data['images']);
            }

            // ========================
            // UPDATE BUSINESS - Now images won't be overwritten
            // ========================
            $business->fill($request_data);
            $business->save();

            DB::commit();

            return response([
                "success" => true,
                "message" => "Business updated successfully",
                "data" => $business
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/v1.0/businesses/{id}",
     *     operationId="getBusinessById",
     *     tags={"business_management"},
     *     summary="Get a single business by ID (role: Super Admin only)",
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
        $business = $this->businessOwnerCheck($id);

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
     *     summary="Get list of all businesses (role: Super Admin only)",
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

        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $query = Business::query();

        $businesses = retrieve_data($query, 'created_at', 'businesses');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Businesses retrieved successfully',
            'meta' => $businesses['meta'],
            'data' => $businesses['data'],
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/businesses/{ids}",
     *     operationId="deleteBusiness",
     *     tags={"business_management"},
     *     summary="Delete Businesses (role: Super Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Business ID (comma-separated for multiple)",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson not found")
     *         )
     *     )
     * )
     */
    public function deleteBusiness($ids)
    {
        try {

            if (!auth()->user()->hasRole('super_admin')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            DB::beginTransaction();

            // Validate and convert comma-separated IDs to an array
            $idsArray = array_map('intval', explode(',', $ids));

            // Get existing users
            $businesses = Business::whereIn('id', $idsArray)->get();

            $existingIds = $businesses->pluck('id')->toArray();

            if (count($existingIds) !== count($idsArray)) {
                $missingIds = array_diff($idsArray, $existingIds);

                return response()->json([
                    'success' => false,
                    'message' => 'Business not found',
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
                'message' => 'Business deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
