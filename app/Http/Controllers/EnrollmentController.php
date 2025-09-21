<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Rules\ValidCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


/**
 * @OA\Tag(
 *     name="Enrollments",
 *     description="Endpoints for managing course enrollments"
 * )
 */
class EnrollmentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/v1.0/enrollments",
     *     operationId="createEnrollment",
     *     tags={"Enrollments"},
     *     summary="Enroll authenticated user in a course",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id"},
     *             @OA\Property(property="course_id", type="integer", example=101)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Enrollment successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=101),
     *             @OA\Property(property="enrolled_at", type="string", example="2025-09-16 12:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request payload.")
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to enroll in this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Already enrolled",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User is already enrolled in this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The course_id field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="course_id", type="array",
     *                     @OA\Items(type="string", example="The course_id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while processing the enrollment.")
     *         )
     *     )
     * )
     */

    public function createEnrollment(Request $request)
    {
        // VALIDATE PAYLOAD
        $request->validate([
            'course_id' => ['required', 'integer', new ValidCourse()],
        ]);

        // GET AUTHENTICATED USER
        $user = Auth::user();

        $exists = Enrollment::where('user_id', $user->id)
            ->where('course_id', $request->course_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User is already enrolled in this course.',
            ], 409); // Conflict
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $request->course_id,
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully',
            'data' => $enrollment,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/users/{id}/enrollments",
     *     tags={"Enrollments"},
     *     summary="Get all enrollments of a specific user",
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
     *         description="List of user enrollments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User enrollments retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="enrollment_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="course",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=101),
     *                         @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                         @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                         @OA\Property(property="price", type="number", example=49.99)
     *                     ),
     *                     @OA\Property(property="progress", type="integer", example=100),
     *                     @OA\Property(property="status", type="string", example="completed"),
     *                     @OA\Property(property="enrolled_at", type="string", example="2025-09-16 12:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid input"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User does not have access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */

    public function userEnrollments($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $enrollments = Enrollment::with('course')
            ->where('user_id', $id)
            ->get();

        $data = $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;

            return [
                'enrollment_id' => $enrollment->id,
                'course' => $course ? [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'price' => $course->price,
                ] : null,
                'progress' => $enrollment->progress,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'User enrollments retrieved successfully',
            'data' => $data,
        ], 200);
    }
}
