<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;


/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Endpoints for fetching LMS dashboard data"
 * )
 */
class DashboardController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * @OA\Get(
     *     path="/v1.0/dashboard",
     *     tags={"Dashboard"},
     *     operationId="getDashboardData",
     *     summary="Get dashboard metrics and trends",
     *     description="Fetches all KPI metrics, revenue & enrollment trends, course completion rates, weekly enrollment trends, and recent student activities for the LMS dashboard. All data can be filtered by date range.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year for monthly trends (defaults to current year)",
     *         required=false,
     *         @OA\Schema(type="integer", example=2025)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for filtering KPI data (DD-MM-YYYY format). Defaults to first day of current month.",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="01-01-2025")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for filtering KPI data (DD-MM-YYYY format). Defaults to last day of current month.",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="31-01-2025")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="date_range", type="object",
     *                 @OA\Property(property="start_date", type="string", format="date"),
     *                 @OA\Property(property="end_date", type="string", format="date")
     *             ),
     *             @OA\Property(property="kpi", type="object",
     *                 @OA\Property(property="total_revenue", type="number", example=124560),
     *                 @OA\Property(property="active_students", type="integer", example=2847),
     *                 @OA\Property(property="enrollment_rate", type="number", example=89.2),
     *                 @OA\Property(property="completion_rate", type="number", example=76.8),
     *                 @OA\Property(property="avg_learning_hours", type="number", example=4.2),
     *                 @OA\Property(property="audience_reach", type="number", example=67.3)
     *             ),
     *             @OA\Property(property="revenue_enrollment_trends", type="object",
     *                 @OA\Property(property="months", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="revenue", type="array", @OA\Items(type="number")),
     *                 @OA\Property(property="enrollment", type="array", @OA\Items(type="integer"))
     *             ),
     *             @OA\Property(property="course_completion_rates", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="completion_rate", type="number")
     *                 )
     *             ),
     *             @OA\Property(property="weekly_enrollment_trends", type="object",
     *                 @OA\Property(property="weeks", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="enrollments", type="array", @OA\Items(type="integer")),
     *                 @OA\Property(property="completions", type="array", @OA\Items(type="integer"))
     *             ),
     *             @OA\Property(property="recent_activities", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="user", type="string"),
     *                     @OA\Property(property="action", type="string"),
     *                     @OA\Property(property="course", type="string"),
     *                     @OA\Property(property="time", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid date format",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid date format. Please use DD-MM-YYYY format.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User does not have required role",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You can not perform this action")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Get year for monthly trends (defaults to current year)
        $year = $request->get('year', now()->year);

        // Get date filters with default to current month (for KPI metrics)
        $startDate = $request->get('start_date', now()->startOfMonth()->format('d-m-Y'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('d-m-Y'));

        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        // Validate date format
        try {
            $startDate = Carbon::createFromFormat('d-m-Y', $startDate)->startOfDay();
            $endDate = Carbon::createFromFormat('d-m-Y', $endDate)->endOfDay();
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Invalid date format. Please use DD-MM-YYYY format."
            ], 400);
        }

        // 1️⃣ KPI Metrics (filtered by date range)

        // TOTAL REVENUE
        $total_revenue = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        // ACTIVE STUDENTS
        $active_students = User::role('student')
            ->whereHas('enrollments', function ($query) use ($startDate, $endDate) {
                $query
                    ->where('progress', '>', 0)
                    ->where('progress', '<', 100)
                    // ->where('expiry_date', '>=', now())
                    ->whereBetween('enrolled_at', [$startDate, $endDate]);
            })
            ->count();

        // ALL STUDENTS
        $all_students = User::role('student')->count();

        // ENROLLED STUDENTS
        $students_enrolled = User::role('student')
            ->whereHas('enrollments', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('enrolled_at', [$startDate, $endDate]);
            })
            ->count();

        // ENROLLMENT RATE
        $enrollment_rate = $all_students ? ($students_enrolled / $all_students) * 100 : 0;

        // COURSE COMPLETION RATE
        $completion_rate = Enrollment::whereBetween('enrolled_at', [$startDate, $endDate])->count()
            ? Enrollment::whereBetween('enrolled_at', [$startDate, $endDate])
            ->where('progress', '>=', 100)->count() / Enrollment::whereBetween('enrolled_at', [$startDate, $endDate])
            ->where('progress', '>', 0)
            ->where('progress', '<', 100)
            ->count() * 100
            : 0;

        // AVG LEARNING HOURS
        $avg_learning_hours = LessonProgress::whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_time_spent') ?? 0;


        // 2️⃣ Revenue & Enrollment Trends (Jan - Dec for the specified year)
        $monthlyTrends = $this->reportService->revenueEnrollmentTrends($year);

        // 3️⃣ Course Completion Rates (filtered by enrollment date)
        $courses = $this->reportService->getCourseCompletionRates($startDate, $endDate);

        // 4️⃣ Weekly Enrollment Trends (within the date range)
        $weeklyTrends = $this->reportService->getWeeklyEnrollmentTrends($startDate, $endDate);

        // 5️⃣ Recent Student Activities (filtered by date range)
        $recent_activities = $this->reportService->getRecentActivities($startDate, $endDate);

        return response()->json([
            'date_range' => [
                'start_date' => $startDate->format('d-m-Y'),
                'end_date' => $endDate->format('d-m-Y')
            ],
            'kpi' => [
                'total_revenue' => $total_revenue,
                'active_students' => $active_students,
                'enrollment_rate' => round($enrollment_rate, 2),
                'completion_rate' => round($completion_rate, 2),
                'avg_learning_hours' => round($avg_learning_hours, 2),
            ],
            'revenue_enrollment_trends' => $monthlyTrends,
            'course_completion_rates' => $courses,
            'weekly_enrollment_trends' => $weeklyTrends,
            'recent_activities' => $recent_activities
        ]);
    }
}
