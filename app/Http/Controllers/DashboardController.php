<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Payment;
use App\Models\User;
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
    /**
     * @OA\Get(
     *     path="/v1.0/dashboard",
     *     tags={"Dashboard"},
     *     operationId="getDashboardData",
     *     summary="Get dashboard metrics and trends",
     *     description="Fetches all KPI metrics, revenue & enrollment trends, course completion rates, weekly enrollment trends, and recent student activities for the LMS dashboard. All data can be filtered by date range.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for filtering data (DD-MM-YYYY format). Defaults to first day of current month.",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="01-01-2025")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for filtering data (DD-MM-YYYY format). Defaults to last day of current month.",
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
        // Get date filters with default to current month
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
        $total_revenue = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        $active_students = User::role('student')
            ->whereHas('enrollments', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'active')
                    ->where('expiry_date', '>=', now())
                    ->whereBetween('enrolled_at', [$startDate, $endDate]);
            })
            ->count();

        $all_students = User::role('student')->count();
        $students_enrolled = User::role('student')
            ->whereHas('enrollments', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('enrolled_at', [$startDate, $endDate]);
            })
            ->count();
        $enrollment_rate = $all_students ? ($students_enrolled / $all_students) * 100 : 0;

        $completion_rate = Enrollment::whereBetween('enrolled_at', [$startDate, $endDate])->count()
            ? Enrollment::whereBetween('enrolled_at', [$startDate, $endDate])
            ->where('progress', '>=', 100)->count() / Enrollment::whereBetween('enrolled_at', [$startDate, $endDate])->count() * 100
            : 0;

        $avg_learning_hours = LessonProgress::whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_time_spent') ?? 0;

        $students_enrolled = Enrollment::whereHas('user', function ($q) {
            $q->role('student');
        })
            ->whereBetween('enrolled_at', [$startDate, $endDate])
            ->distinct('user_id')->count();

        $audience_reach = $all_students ? ($students_enrolled / $all_students) * 100 : 0;

        // 2️⃣ Revenue & Enrollment Trends (within the date range, grouped by months)
        $months = [];
        $revenue_trend = [];
        $enrollment_trend = [];

        $periodStart = $startDate->copy();
        $periodEnd = $endDate->copy();

        // Calculate number of months in the range
        $monthsDiff = $periodStart->diffInMonths($periodEnd) + 1;

        for ($i = 0; $i < $monthsDiff; $i++) {
            $currentMonth = $periodStart->copy()->addMonths($i);
            if ($currentMonth->greaterThan($periodEnd)) break;

            $month = $currentMonth->format('M Y');
            $months[] = $month;

            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();

            $revenue_trend[] = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('amount');

            $enrollment_trend[] = Enrollment::whereBetween('enrolled_at', [$monthStart, $monthEnd])
                ->count();
        }

        // 3️⃣ Course Completion Rates (filtered by enrollment date)
        $courses = Course::all()->map(function ($course) use ($startDate, $endDate) {
            $total_enrollments = Enrollment::where('course_id', $course->id)
                ->whereBetween('enrolled_at', [$startDate, $endDate])
                ->count();
            $completed_enrollments = Enrollment::where('course_id', $course->id)
                ->whereBetween('enrolled_at', [$startDate, $endDate])
                ->where('progress', '>=', 100)
                ->count();
            $completion_rate = $total_enrollments ? ($completed_enrollments / $total_enrollments) * 100 : 0;

            return [
                'name' => $course->title,
                'completion_rate' => round($completion_rate, 2)
            ];
        });

        // 4️⃣ Weekly Enrollment Trends (within the date range)
        $weeks = [];
        $weekly_enrollments = [];
        $weekly_completions = [];

        $weeksDiff = ceil($startDate->diffInDays($endDate) / 7);

        for ($i = 0; $i < $weeksDiff; $i++) {
            $weekStart = $startDate->copy()->addWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            if ($weekStart->greaterThan($endDate)) break;

            $weeks[] = "Week " . ($i + 1) . " (" . $weekStart->format('M d') . ")";

            // Enrollments created this week
            $weekly_enrollments[] = Enrollment::whereBetween('enrolled_at', [$weekStart, $weekEnd])->count();

            // Enrollments completed this week (progress reached 100% within the week)
            $weekly_completions[] = Enrollment::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('progress', '>=', 100)
                ->count();
        }

        // 5️⃣ Recent Student Activities (filtered by date range)
        $recent_activities = Enrollment::with('user', 'course')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($enrollment) {
                $action = $enrollment->progress >= 100 ? 'Completed' : 'Progressed';
                return [
                    'user' => $enrollment->user->first_name . ' ' . $enrollment->user->last_name,
                    'action' => $action,
                    'course' => $enrollment->course->title ?? null,
                    'time' => $enrollment->updated_at->diffForHumans(),
                ];
            });

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
                'audience_reach' => round($audience_reach, 2)
            ],
            'revenue_enrollment_trends' => [
                'months' => $months,
                'revenue' => $revenue_trend,
                'enrollment' => $enrollment_trend
            ],
            'course_completion_rates' => $courses,
            'weekly_enrollment_trends' => [
                'weeks' => $weeks,
                'enrollments' => $weekly_enrollments,
                'completions' => $weekly_completions
            ],
            'recent_activities' => $recent_activities
        ]);
    }
}
