<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
     *     tags={"Trash"},
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
     *         description="Start date (DD-MM-YYYY)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (DD-MM-YYYY)",
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
        // Authorize only owners, admins or lecturers
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                'message' => 'You do not have permission to access this report.'
            ], 403); // 403 Forbidden
        }

        // Determine the reporting period; defaults to current month
        $start = $request->query('start_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end   = $request->query('end_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

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
     *     description="Returns total revenue, average order value, daily revenue trends and per-course performance. Accepts optional start_date and end_date query parameters (DD-MM-YYYY).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for the report (DD-MM-YYYY)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for the report (DD-MM-YYYY)",
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
            ? Carbon::createFromFormat('d-m-Y', $startDate)->startOfDay()
            : Carbon::now()->startOfMonth();
        $end   = $endDate
            ? Carbon::createFromFormat('d-m-Y', $endDate)->endOfDay()
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
                    'course'      => $row->course->title,
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


    /**
     * @OA\Get(
     *     path="/v1.0/reports/overview",
     *     operationId="overviewReport",
     *     tags={"Reports"},
     *     summary="Get overview and student analytics metrics (roles: owner, admin, lecturer)",
     *     description="Returns total students, course completions, total revenue, enrollment trends, active students and new enrollments. Accepts optional start_date and end_date parameters (DD-MM-YYYY).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for the report (DD-MM-YYYY). Defaults to start of current month for overview metrics and last 7 days for active/new enrollments if not provided.",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for the report (DD-MM-YYYY). Defaults to end of current day if not provided.",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Overview metrics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Overview metrics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="totalStudents", type="integer", example=1234),
     *                 @OA\Property(property="courseCompletions", type="integer", example=892),
     *                 @OA\Property(property="totalRevenue", type="number", format="float", example=48650),
     *                 @OA\Property(
     *                     property="enrollmentTrends",
     *                     type="array",
     *                     description="Daily enrollment counts within the selected period",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date", example="2025-01-01"),
     *                         @OA\Property(property="count", type="integer", example=100)
     *                     )
     *                 ),
     *                 @OA\Property(property="activeStudents", type="integer", example=1045),
     *                 @OA\Property(property="newEnrollments", type="integer", example=234)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks required role",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request – invalid parameters",
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


    public function overviewReport(Request $request)
    {
        // Only admins/owners/lecturers can see this
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json(['message' => 'You do not have permission.'], 403);
        }

        // Determine date range (defaults to current month)
        $start = $request->query('start_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $end = $request->query('end_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Count all students (assuming a role/relationship setup)
        $totalStudents = User::whereHas('roles', function ($q) {
            $q->where('name', 'student');
        })->count();

        // Count course completions within the period (assuming completed_at is set)
        $totalCompletions = Enrollment::whereNotNull('progress')
            ->whereBetween('enrolled_at', [$start, $end])
            ->count();

        // Sum payments for total revenue
        $totalRevenue = Payment::whereBetween('paid_at', [$start, $end])->sum('amount');

        // Enrollment trends (daily counts)
        $enrollmentTrends = Enrollment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $start = $request->query('start_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('start_date'))->startOfDay()
            : Carbon::now()->subDays(7)->startOfDay();  // default to last 7 days
        $end   = $request->query('end_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Count active students BASE ON PROGRESS
        $activeStudents = Enrollment::whereBetween('enrolled_at', [$start, $end])
            ->where('progress', '>', 0)
            ->where('progress', '<', 100)
            ->distinct('user_id')
            ->count('user_id');

        // Count new enrollments
        $newEnrollments = Enrollment::whereBetween('enrolled_at', [$start, $end])
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Overview metrics retrieved successfully',
            'data' => [
                'totalStudents'    => $totalStudents,
                'courseCompletions' => $totalCompletions,
                'totalRevenue'     => $totalRevenue,
                'enrollmentTrends' => $enrollmentTrends,
                'activeStudents'  => $activeStudents,
                'newEnrollments'  => $newEnrollments,
            ],
        ]);
    }


    /**
     * @OA\Get(
     *     path="/v1.0/reports/course-performance",
     *     operationId="coursePerformanceReport",
     *     tags={"Reports"},
     *     summary="Get course performance metrics (roles: owner, admin, lecturer)",
     *     description="Returns enrollment counts, completion rates and revenue by course. Accepts optional start_date and end_date query parameters (DD-MM-YYYY).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for the report (DD-MM-YYYY). Defaults to the start of the current month.",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for the report (DD-MM-YYYY). Defaults to the end of the current month.",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course performance metrics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course performance metrics retrieved successfully"),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(
     *                     property="date_range",
     *                     type="object",
     *                     @OA\Property(property="start_date", type="string", format="date", example="01-11-2025"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="30-11-2025")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="List of courses with performance metrics",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="course_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="React Fundamentals"),
     *                     @OA\Property(property="enrollments", type="integer", example=456),
     *                     @OA\Property(property="completion_rate", type="string", example="80%"),
     *                     @OA\Property(property="revenue", type="number", format="float", example=45600)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – user lacks required role",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request – invalid parameters",
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


    public function coursePerformanceReport(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json(['message' => 'You do not have permission.'], 403);
        }

        // Parse dates in DD-MM-YYYY format, default to current month
        $start_date = $request->query('start_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $end_date = $request->query('end_date')
            ? Carbon::createFromFormat('d-m-Y', $request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        $courses = Course::withCount([
            // Enrollments within the period
            'enrollments' => function ($q) use ($start_date, $end_date) {
                $q->whereBetween('enrolled_at', [$start_date, $end_date]);
            },
            // Completions within the period (alias completions_count)
            'enrollments as completions_count' => function ($q) use ($start_date, $end_date) {
                $q->where('progress', 100)
                    ->whereBetween('enrolled_at', [$start_date, $end_date]);
            },
        ])
            ->withSum(['payments as revenue_sum' => function ($q) use ($start_date, $end_date) {
                $q->whereBetween('paid_at', [$start_date, $end_date]);
            }], 'amount')
            ->get()
            ->map(function ($course) {
                $enrollments    = $course->enrollments_count ?? 0;
                $completions    = $course->completions_count ?? 0;
                $completionRate = $enrollments > 0 ? round(($completions / $enrollments) * 100) : 0;
                return [
                    'course_id'       => $course->id,
                    'title'           => $course->title,
                    'enrollments'     => $enrollments,
                    'completion_rate' => $completionRate . '%',
                    'revenue'         => $course->revenue_sum ?? 0,
                ];
            });

        // (Optionally) compute completion rate trends here if needed

        return response()->json([
            'success' => true,
            'message' => 'Course performance metrics retrieved successfully',
            'meta' => [
                'date_range' => [
                    'start_date' => $start_date->format('d-m-Y'),
                    'end_date' => $end_date->format('d-m-Y'),
                ]
            ],
            'data'    => $courses,
        ]);
    }
}
