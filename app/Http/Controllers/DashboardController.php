<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Payment;
use App\Models\User;
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
     *     description="Fetches all KPI metrics, revenue & enrollment trends, course completion rates, weekly enrollment trends, and recent student activities for the LMS dashboard.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
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
     *     )
     * )
     */
    public function index()
    {

        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }
        // 1️⃣ KPI Metrics
        $total_revenue = Payment::where('status', 'completed')->sum('amount');

        $active_students = User::role('student')
            ->whereHas('enrollments', function ($query) {
                $query->where('status', 'active')
                    ->where('expiry_date', '>=', now());
            })
            ->count();

        $all_students = User::role('student')->count();
        $students_enrolled = User::role('student')->whereHas('enrollments')->count();
        $enrollment_rate = $all_students ? ($students_enrolled / $all_students) * 100 : 0;

        $completion_rate = Enrollment::count()
            ? Enrollment::where('progress', '>=', 100)->count() / Enrollment::count() * 100
            : 0;



        $avg_learning_hours = LessonProgress::avg('total_time_spent') ?? 0;

        $students_enrolled = Enrollment::whereHas('user', function ($q) {
            $q->role('student');
        })->distinct('user_id')->count();

        $audience_reach = $all_students ? ($students_enrolled / $all_students) * 100 : 0;

        // 2️⃣ Revenue & Enrollment Trends (last 6 months)
        $months = [];
        $revenue_trend = [];
        $enrollment_trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('M');
            $months[] = $month;
            $revenue_trend[] = Payment::whereMonth('paid_at', now()->subMonths($i)->month)->where('status', 'completed')->sum('amount');
            $enrollment_trend[] = Enrollment::whereMonth('enrolled_at', now()->subMonths($i)->month)->count();
        }

        // 3️⃣ Course Completion Rates
        $courses = Course::all()->map(function ($course) {
            $total_enrollments = Enrollment::where('course_id', $course->id)->count();
            $completed_enrollments = Enrollment::where('course_id', $course->id)
                ->where('progress', '>=', 100)
                ->count();
            $completion_rate = $total_enrollments ? ($completed_enrollments / $total_enrollments) * 100 : 0;

            return [
                'name' => $course->title,
                'completion_rate' => round($completion_rate, 2)
            ];
        });


        // 5️⃣ Weekly Enrollment Trends (last 6 weeks)
        $weeks = [];
        $weekly_enrollments = [];
        $weekly_completions = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = now()->endOfWeek()->subWeeks($i);
            $weeks[] = "Week " . (6 - $i);

            // Enrollments created this week
            $weekly_enrollments[] = Enrollment::whereBetween('enrolled_at', [$start, $end])->count();

            // Enrollments completed this week (progress reached 100% within the week)
            $weekly_completions[] = Enrollment::whereBetween('updated_at', [$start, $end])
                ->where('progress', '>=', 100)
                ->count();
        }


        // 6️⃣ Recent Student Activities (based on enrollment progress)
        $recent_activities = Enrollment::with('user', 'course')
            ->latest('updated_at') // when progress was last updated
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
