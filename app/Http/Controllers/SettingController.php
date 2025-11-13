<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessSettingRequest;
use App\Models\BusinessSetting;
use Exception;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class SettingController extends Controller
{
    /**
     *
     * @OA\Put(
     *      path="/v1.0/business-settings",
     *      operationId="updateBusinessSettings",
     *      tags={"setting"},
     *      security={{"bearerAuth": {}}},
     *      summary="Update business settings (role: Admin/Owner/Lecturer only)",
     *      description="Updates business settings. All fields are optional; only passed fields will be updated.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *
     *
     *              @OA\Property(property="general__main_color", type="string"),
     *              @OA\Property(property="general__secondary_color", type="string"),
     *              @OA\Property(property="general__accent_color", type="string"),
     *              @OA\Property(property="general__danger_color", type="string"),
     *              @OA\Property(property="general__warning_color", type="string"),
     *              @OA\Property(property="general__success_color", type="string"),
     *              @OA\Property(property="general__featured_courses_count", type="integer"),
     *  *              @OA\Property(property="general__popular_courses_count", type="integer"),
     * 
     *              @OA\Property(property="general__loading_animation", type="string"),
     *
     *
     *              @OA\Property(property="courses__import_demo_courses", type="boolean"),
     *              @OA\Property(property="courses__courses_page", type="string"),
     *              @OA\Property(property="courses__courses_page_layout", type="string"),
     *              @OA\Property(property="courses__courses_per_row", type="integer"),
     *              @OA\Property(property="courses__courses_per_page", type="integer"),
     *              @OA\Property(property="courses__load_more_type", type="string"),
     *              @OA\Property(property="courses__course_card_style", type="string"),
     *              @OA\Property(property="courses__course_card_info_position", type="string"),
     *              @OA\Property(property="courses__course_image_size", type="string"),
     *              @OA\Property(property="courses__lazy_loading", type="boolean"),
     *              @OA\Property(property="courses__category_slug", type="string"),
     *              @OA\Property(property="courses__show_featured_courses_on_top", type="boolean"),
     *              @OA\Property(property="courses__featured_courses_count", type="integer"),
     *              @OA\Property(property="courses__filters_on_archive_page", type="boolean"),
     *
     *
     *              @OA\Property(property="course__page_style", type="string"),
     *              @OA\Property(property="course__show_course_reviews", type="boolean"),
     *              @OA\Property(property="course__default_tab", type="string"),
     *              @OA\Property(property="course__use_emoji_in_quizzes", type="boolean"),
     *              @OA\Property(property="course__show_description_tab", type="boolean"),
     *              @OA\Property(property="course__show_curriculum_tab", type="boolean"),
     *              @OA\Property(property="course__show_faq_tab", type="boolean"),
     *              @OA\Property(property="course__show_notice_tab", type="boolean"),
     *              @OA\Property(property="course__course_levels", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="course__allow_presto_player", type="boolean"),
     *              @OA\Property(property="course__auto_enroll_free_courses", type="boolean"),
     *              @OA\Property(property="course__allow_reviews_non_enrolled", type="boolean"),
     *              @OA\Property(property="course__allow_basic_info_section", type="boolean"),
     *              @OA\Property(property="course__allow_course_requirements_section", type="boolean"),
     *              @OA\Property(property="course__allow_intended_audience_section", type="boolean"),
     *              @OA\Property(property="course__preferred_video_sources", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="course__preferred_audio_sources", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="course__bottom_sticky_panel", type="boolean"),
     *              @OA\Property(property="course__show_popular_courses", type="boolean"),
     *              @OA\Property(property="course__show_related_courses", type="boolean"),
     *              @OA\Property(property="course__disable_default_completion_image", type="boolean"),
     *              @OA\Property(property="course__failed_course_image", type="string"),
     *              @OA\Property(property="course__passed_course_image", type="string"),
     *
     *
     *              @OA\Property(property="certificate__threshold", type="integer"),
     *              @OA\Property(property="certificate__allow_instructor_create", type="boolean"),
     *              @OA\Property(property="certificate__use_current_student_name", type="boolean"),
     *              @OA\Property(property="certificate__builder_data", type="array", @OA\Items(type="object")),
     *
     *
     *              @OA\Property(property="stripe_enabled", type="boolean"),
     *              @OA\Property(property="STRIPE_KEY", type="string"),
     *              @OA\Property(property="STRIPE_SECRET", type="string")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="Not Found"),
     *      @OA\Response(response=422, description="Unprocessable Content"),
     *      @OA\Response(response=400, description="Bad Request")
     * )
     */




    public function updateBusinessSettings(UpdateBusinessSettingRequest $request)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You cannot perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            $file_fields = [
                'course__failed_course_image',
                'course__passed_course_image',
                'general__loading_animation', // if it's a file
            ];

            foreach ($file_fields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = $file->hashName();
                    $folder_path = "business_1/{$field}";
                    $file->storeAs($folder_path, $filename, 'public');
                    $request_data[$field] = $filename;
                }

                // If existing path string is passed
                if ($request->filled($field) && is_string($request->input($field))) {
                    $request_data[$field] = basename($request->input($field));
                }
            }


            // Stripe validation if stripe_enabled is passed and true
            if (!empty($request_data['stripe_enabled'])) {
                try {
                    $stripe = new StripeClient($request_data['STRIPE_SECRET'] ?? '');
                    $stripe->balance->retrieve();
                } catch (\Exception $e) {
                    return response()->json([
                        "message" => "Stripe error: " . $e->getMessage()
                    ], 400);
                }
            }

            $businessSetting = BusinessSetting::first();

            if (!$businessSetting) {
                $businessSetting = BusinessSetting::create($request_data);
            } else {
                $businessSetting->fill($request_data);
                $businessSetting->save();
            }

            return response()->json($businessSetting, 200);
        } catch (Exception $e) {
            return response()->json([
                "message" => "An error occurred while updating business settings: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-settings",
     *      operationId="getBusinessSettings",
     *      tags={"setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get busuness setting (role: Admin only)",
     *      description="This method is to get busuness setting",
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

    public function getBusinessSettings(Request $request)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $busunessSetting = BusinessSetting::first();

            if ($busunessSetting) {
                $busunessSettingArray = $busunessSetting->toArray();
                $busunessSettingArray["STRIPE_SECRET"] = $busunessSetting->STRIPE_SECRET;
            } else {
                $busunessSettingArray["STRIPE_KEY"] = NULL;
                $busunessSettingArray["STRIPE_SECRET"] = NULL;
            }


            return response()->json($busunessSettingArray, 200);
        } catch (Exception $e) {

            return response()->json([
                "message" => "An error occurred while retrieving business settings: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/business-settings",
     *      operationId="getBusinessSettingsClient",
     *      tags={"setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *      * *  @OA\Parameter(
     * name="start_date",
     * in="query",
     * description="start_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="end_date",
     * in="query",
     * description="end_date",
     * required=true,
     * example="2019-06-29"
     * ),
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *    * *  @OA\Parameter(
     * name="business_tier_id",
     * in="query",
     * description="business_tier_id",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get Business setting (role: Admin only)",
     *      description="This method is to get Business_setting",
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

    public function getBusinessSettingsClient(Request $request)
    {
        try {

            $busunessSetting = BusinessSetting::first();

            if ($busunessSetting) {
                $businessSettingArray = $busunessSetting->toArray();

                $businessSettingArray["STRIPE_KEY"] = $busunessSetting->STRIPE_KEY;
            } else {
                // Handle the case where no BusinessSetting is found, if necessary
                $businessSettingArray["STRIPE_KEY"] = null; // or any default value you'd prefer
            }

            return response()->json($businessSettingArray, 200);
        } catch (Exception $e) {

            return response()->json([
                "message" => "An error occurred while retrieving business settings: " . $e->getMessage()
            ], 500);
        }
    }
}
