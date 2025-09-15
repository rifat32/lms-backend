<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
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
     *     path="/api/enrollments",
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
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
        ]);

        $user = Auth::user();

        // Prevent duplicate enrollment
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $request->course_id],
            ['enrolled_at' => now()]
        );

        return response()->json($enrollment, 201);
    }

  /**
     * @OA\Get(
     *     path="/api/users/{id}/enrollments",
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
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="enrollment_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="course",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=101),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                     @OA\Property(property="price", type="number", example=49.99)
     *                 ),
     *                 @OA\Property(property="progress", type="integer", example=100),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="enrolled_at", type="string", example="2025-09-16 12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function userEnrollments($id)
    {
        $enrollments = Enrollment::with('course')
            ->where('user_id', $id)
            ->get();

        $data = $enrollments->map(function($enrollment) {
            return [
                'enrollment_id' => $enrollment->id,
                'course' => [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'description' => $enrollment->course->description,
                    'price' => $enrollment->course->price,
                ],
                'progress' => $enrollment->progress,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at,
            ];
        });

        return response()->json($data);
    }
}
