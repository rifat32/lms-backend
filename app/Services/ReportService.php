<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get monthly revenue and enrollment trends for a specific year (Jan - Dec)
     * 
     * @param int|null $year Year to get trends for (defaults to current year)
     * @return array
     */
    public function revenueEnrollmentTrends(?int $year = null): array
    {
        $year = $year ?? now()->year;
        $trends = [];

        // Loop through all 12 months
        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($year, $month, 1);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            // Revenue for this month
            $revenue = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('amount');

            // Enrollments for this month
            $enrollment = Enrollment::whereBetween('enrolled_at', [$monthStart, $monthEnd])
                ->count();

            $trends[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue,
                'enrollment' => $enrollment
            ];
        }

        return $trends;
    }

    /**
     * Get monthly revenue and enrollment trends within a date range
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function revenueEnrollmentTrendsByDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $periodStart = $startDate->copy();
        $periodEnd = $endDate->copy();

        // Calculate number of months in the range
        $monthsDiff = $periodStart->diffInMonths($periodEnd) + 1;

        for ($i = 0; $i < $monthsDiff; $i++) {
            $currentMonth = $periodStart->copy()->addMonths($i);
            if ($currentMonth->greaterThan($periodEnd)) break;

            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();

            // Adjust to respect the original date range boundaries
            if ($monthStart->lessThan($periodStart)) {
                $monthStart = $periodStart->copy();
            }
            if ($monthEnd->greaterThan($periodEnd)) {
                $monthEnd = $periodEnd->copy();
            }

            // Revenue for this period
            $revenue = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('amount');

            // Enrollments for this period
            $enrollment = Enrollment::whereBetween('enrolled_at', [$monthStart, $monthEnd])
                ->count();

            $trends[] = [
                'month' => $monthStart->format('d M') . ' - ' . $monthEnd->format('d M Y'),
                'revenue' => $revenue,
                'enrollment' => $enrollment
            ];
        }

        return $trends;
    }

    /**
     * Get weekly enrollment trends within a date range
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getWeeklyEnrollmentTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $weeksDiff = ceil($startDate->diffInDays($endDate) / 7);

        for ($i = 0; $i < $weeksDiff; $i++) {
            $weekStart = $startDate->copy()->addWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            if ($weekStart->greaterThan($endDate)) break;

            // Enrollments created this week
            $enrollments = Enrollment::whereBetween('enrolled_at', [$weekStart, $weekEnd])->count();

            // Enrollments completed this week
            $completions = Enrollment::whereBetween('updated_at', [$weekStart, $weekEnd])
                ->where('progress', '>=', 100)
                ->count();

            $trends[] = [
                'week' => "Week " . ($i + 1) . " (" . $weekStart->format('M d') . ")",
                'enrollments' => $enrollments,
                'completions' => $completions
            ];
        }

        return $trends;
    }

    /**
     * Get course completion rates within a date range
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Support\Collection
     */
    public function getCourseCompletionRates(Carbon $startDate, Carbon $endDate)
    {
        return Course::all()->map(function ($course) use ($startDate, $endDate) {
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
    }

    /**
     * Get recent student activities within a date range
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecentActivities(Carbon $startDate, Carbon $endDate, int $limit = 5)
    {
        return Enrollment::with('user', 'course')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->latest('updated_at')
            ->take($limit)
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
    }
}
