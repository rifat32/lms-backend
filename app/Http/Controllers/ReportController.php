<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Endpoints to fetch sales and enrollment reports (Admin only)"
 * )
 */
class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/reports/sales",
     *     operationId="sales",
     *     tags={"Reports"},
     *     summary="Get sales report for all courses (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sales report retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales report retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="course_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="total_revenue", type="number", example=5000),
     *                     @OA\Property(property="total_sales_count", type="integer", example=20)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid parameters"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only admin users can access this report"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */

    public function sales()
    {
        $courses = Course::with('payments')->get();

        $report = $courses->map(function ($course) {
            $total_revenue = $course->payments()->where('status', 'completed')->sum('amount');
            $total_sales_count = $course->payments()->where('status', 'completed')->count();

            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'total_revenue' => $total_revenue,
                'total_sales_count' => $total_sales_count,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Sales report retrieved successfully',
            'data' => $report
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/reports/enrollments",
     *     operationId="enrollmentReport",
     *     tags={"Reports"},
     *     summary="Get enrollment count per course (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment report retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Enrollment report retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="course_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="enrolled_students_count", type="integer", example=25)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request parameters.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only admin users can access this report",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this report.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Resource not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while retrieving the report.")
     *         )
     *     )
     * )
     */

    public function enrollments()
    {
        $courses = Course::with('enrollments')->get();

        $report = $courses->map(function ($course) {
            $enrolled_students_count = $course->enrollments()->count();

            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'enrolled_students_count' => $enrolled_students_count,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Enrollment report retrieved successfully',
            'data' => $report
        ]);
    }
}
