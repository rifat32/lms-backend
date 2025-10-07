<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseCategoryRequest;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/course-categories",
     *     operationId="getCourseCategory",
     *     tags={"course_management.course_category"},
     *     summary="Get all course categories",
     *     description="Retrieve a paginated list of course categories.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=10, example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of course categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Web Development"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No categories found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No course categories found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid query parameters")
     *         )
     *     )
     * )
     */


    public function getCourseCategory(Request $request)
    {
        // 
        $query = CourseCategory::
        with([
            'parent' => function ($q) {
                $q->select('course_categories.id', 'course_categories.name');
            }
            
            ])
        ->withCount(['courses as total_courses'])->filters();

        // 
        $courses = retrieve_data($query, 'created_at', 'course_categories');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Course categories retrieved successfully',
            'meta' => $courses['meta'],
            'data' => $courses['data'],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/course-categories/{id}",
     *     operationId="getCourseCategoryById",
     *     tags={"course_management.course_category"},
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
     *             @OA\Property(property="description", type="string", example="description"),
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
     *     tags={"course_management.course_category"},
     *     summary="Create a new course category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Data Science"),
     *             @OA\Property(property="parent_id", type="integer", example=""),
     *             @OA\Property(property="description", type="string", example="description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="name", type="string", example="Data Science"),
     *             @OA\Property(property="parent_id", type="integer", example="1"),
     *             @OA\Property(property="description", type="string", example="description"),
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
     *     tags={"course_management.course_category"},
     *     summary="Update a course category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "name"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="parent_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Data Science"),
     *             @OA\Property(property="description", type="string", example="description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="parent_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Data Science"),
     *        @OA\Property(property="description", type="string", example="description"),
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

    /**
     * @OA\Delete(
     *     path="/v1.0/course-categories/{ids}",
     *     operationId="deleteCourseCategory",
     *     tags={"course_management.course_category"},
     *     summary="Delete a course category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Course Category ID (comma-separated for multiple)",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Some data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Some data not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course category not found")
     *         )
     *     )
     * )
     */

    public function deleteCourseCategory($ids)
    {
        try {
            DB::beginTransaction();

            $idsOfArray = array_map('intval', explode(',', $ids));

            // VALIDATE PAYLOAD
            $existingIds = CourseCategory::whereIn('id', $idsOfArray)->pluck('id')->toArray();

            if (count($existingIds) !== count($idsOfArray)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some data not found',
                    'data' => $existingIds
                ], 400);
            }

            // DELETE THE RECORDS
            CourseCategory::whereIn('id', $existingIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course category deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
