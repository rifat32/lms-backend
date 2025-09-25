<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseCategoryRequest;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Courses",
 *     description="Endpoints for managing courses"
 * )
 */
class CourseCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/course-categories",
     *     operationId="getCourseCategories",
     *     tags={"course_category"},
     *     summary="Get all course categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of course categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Web Development"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No categories found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No course categories found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid query parameters")
     *         )
     *     )
     * )
     */

    public function getCourseCategory(Request $request)
    {
        // 
        $courses = CourseCategory::filters()->get();

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Course categories retrieved successfully',
            'data' => $courses,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/course-categories/{id}",
     *     operationId="getCourseCategoryById",
     *     tags={"course_category"},
     *     summary="Get a single course category by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course Category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Web Development"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden: Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to view this course category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course Category not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict: Resource conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Conflict occurred while retrieving this resource")
     *         )
     *     )
     * )
     */


    public function getCourseCategoryById($id)
    {
        $course = CourseCategory::findOrFail($id);
        return response()->json([
            'success' => true,
            'message' => 'Course category retrieved successfully',
            'data' => $course
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1.0/course-categories",
     *     operationId="createCourseCategory",
     *     tags={"course_category"},
     *     summary="Create a new course category (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Data Science")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="name", type="string", example="Data Science"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden: Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to create a course category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Requested resource not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict: Course category already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A course category with this name already exists")
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
     *                 )
     *             )
     *         )
     *     )
     * )
     */


    public function createCourseCategory(CourseCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // CREATE
            $course = CourseCategory::create($request_payload);

            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Course category created successfully',
                'data' => $course
            ], 201);
        } catch (\Throwable $th) {
            // ROLLBACK TRANSACTION
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Put(
     *     path="/v1.0/course-categories",
     *     operationId="updateCourseCategory",
     *     tags={"course_category"},
     *     summary="Update a course category (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "name"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Data Science")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Data Science"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden: Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update this course category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course category not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict: Course category already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A course category with this name already exists")
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
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function updateCourseCategory(CourseCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // FIND THE COURSE CATEGORY
            $course = CourseCategory::find($request_payload['id']);

            if (empty($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course category not found',
                ], 404);
            }

            // UPDATE
            $course->update($request_payload);

            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Course category updated successfully',
                'data' => $course
            ], 200);
        } catch (\Throwable $th) {
            // ROLLBACK TRANSACTION
            DB::rollBack();
            throw $th;
        }
    }
}
