<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
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
     *     operationId="enrollmentAnalytics",
     *     tags={"Reports"},
     *     summary="Get enrollment analytics (role: Owner/Admin/Lecturer)",
     *     description="Returns total enrollments, average daily enrollments, daily trends and course performance within an optional date range.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analytics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Enrollment analytics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="totalEnrollments", type="integer", example=2847),
     *                 @OA\Property(property="avgDailyEnrollments", type="number", format="float", example=94.0),
     *                 @OA\Property(
     *                     property="enrollmentTrends",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date", example="2025-02-01"),
     *                         @OA\Property(property="count", type="integer", example=80)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="coursePerformance",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="course_id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="React Fundamentals"),
     *                         @OA\Property(property="enrolled_students_count", type="integer", example=234)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – insufficient privileges",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this report.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request parameters.")
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


    public function enrollmentAnalytics(Request $request)
    {
        // Authorise only owners, admins or lecturers
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                'message' => 'You do not have permission to access this report.'
            ], 403); // 403 Forbidden – authenticated but lacks privileges:contentReference[oaicite:3]{index=3}
        }

        // Determine the reporting period; defaults to current month
        $start = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end   = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Total enrollments in the period
        $totalEnrollments = Enrollment::whereBetween('created_at', [$start, $end])->count();

        // Number of days in the period for averaging
        $days = $start->diffInDays($end) + 1;
        $avgDailyEnrollments = $days > 0 ? round($totalEnrollments / $days, 2) : 0;

        // Enrollment trends: group by date and count
        $trends = Enrollment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Course performance: count enrollments per course efficiently
        $coursePerformance = Course::withCount(['enrollments' => function ($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        }])
            ->get()
            ->map(function ($course) {
                return [
                    'course_id' => $course->id,
                    'title'     => $course->title,
                    'enrolled_students_count' => $course->enrollments_count,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Enrollment analytics retrieved successfully',
            'data' => [
                'totalEnrollments'      => $totalEnrollments,
                'avgDailyEnrollments'   => $avgDailyEnrollments,
                'enrollmentTrends'      => $trends,
                'coursePerformance'     => $coursePerformance,
            ],
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
