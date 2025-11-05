<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

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
     *     summary="Get sales report for all courses (role: Admin only)",
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
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

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
     *     summary="Get enrollment count per course (role: Admin only)",
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
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $courses = Course::with('enrollments')->get();

        $enrollment_courses = $courses->map(function ($course) {
            $enrolled_students_count = $course->enrollments()->count();

            return [
                'course_id' => $course->id,
                'title' => $course->title,
                'enrolled_students_count' => $enrolled_students_count,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Enrollment courses retrieved successfully',
            'data' => $enrollment_courses
        ]);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/reports/revenue",
     *     operationId="revenueReport",
     *     tags={"Reports"},
     *     summary="Get revenue report per course (role: Admin only)",
     *     description="Returns total revenue, average order value, daily revenue trends and per-course performance. Accepts optional start_date and end_date query parameters (YYYY-MM-DD).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for the report (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for the report (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Revenue report retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Revenue report retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="totalRevenue", type="number", format="float", example=142560),
     *                 @OA\Property(property="averageOrderValue", type="number", format="float", example=195),
     *                 @OA\Property(
     *                     property="revenueTrends",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date", example="2025-02-01"),
     *                         @OA\Property(property="revenue", type="number", format="float", example=22500)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="coursePerformance",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="course_id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="React Fundamentals"),
     *                         @OA\Property(property="revenue", type="number", format="float", example=45600),
     *                         @OA\Property(property="enrollments", type="integer", example=234),
     *                         @OA\Property(property="aov", type="number", format="float", example=195),
     *                         @OA\Property(property="change", type="number", format="float", nullable=true, example=18.0)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request – Invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request parameters.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized – Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – Only admin users can access this report",
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

    public function revenueReport(Request $request)
    {
        // Parse requested date range or default to current month
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::now()->startOfMonth();
        $end   = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : Carbon::now()->endOfDay();

        // total revenue for the period
        $totalRevenue = Payment::whereBetween('paid_at', [$start, $end])
            ->sum('amount');            // `sum` is one of the aggregate methods:contentReference

        // average order value for the period
        $averageOrderValue = Payment::whereBetween('paid_at', [$start, $end])
            ->avg('amount');

        // revenue trends (daily totals)
        $revenueTrends = Payment::selectRaw('DATE(paid_at) as date, SUM(amount) as revenue')
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('date')
            ->get();

        // length of period used later to compute previous period
        $daysInPeriod = $start->diffInDays($end) + 1;

        // course revenue performance
        $coursePerformance = Payment::select(
            'course_id',
            DB::raw('SUM(amount) as revenue'),
            DB::raw('COUNT(*) as enrollments'),
            DB::raw('AVG(amount) as aov')
        )
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('course_id')
            ->with('course') // eager‑load related course
            ->get()
            ->map(function ($row) use ($start, $daysInPeriod) {
                // compute previous period (same length immediately before start)
                $prevStart = $start->copy()->subDays($daysInPeriod);
                $prevEnd   = $start->copy()->subDay();

                // revenue of the same course in previous period
                $prevRevenue = Payment::where('course_id', $row->course_id)
                    ->whereBetween('paid_at', [$prevStart, $prevEnd])
                    ->sum('amount');

                // percentage change compared with previous period
                $change = $prevRevenue > 0
                    ? round(($row->revenue - $prevRevenue) / $prevRevenue * 100, 2)
                    : null;

                return [
                    'course'      => $row->course->name,
                    'revenue'     => (float) $row->revenue,
                    'enrollments' => (int) $row->enrollments,
                    'aov'         => (float) $row->aov,
                    'change'      => $change,
                ];
            });

        return response()->json([
            'totalRevenue'      => (float) $totalRevenue,
            'averageOrderValue' => (float) $averageOrderValue,
            'revenueTrends'     => $revenueTrends,
            'coursePerformance' => $coursePerformance,
        ]);
    }
}
