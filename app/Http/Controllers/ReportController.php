<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Enrollment;

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
     *     path="/api/reports/sales",
     *     tags={"Reports"},
     *     summary="Get sales report for all courses (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sales report retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                 @OA\Property(property="total_revenue", type="number", example=5000),
     *                 @OA\Property(property="total_sales_count", type="integer", example=20)
     *             )
     *         )
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

        return response()->json($report);
    }

   /**
     * @OA\Get(
     *     path="/api/reports/enrollments",
     *     tags={"Reports"},
     *     summary="Get enrollment count per course (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment report retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                 @OA\Property(property="enrolled_students_count", type="integer", example=25)
     *             )
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

        return response()->json($report);
    }
}