<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Enrollment;

class ReportController extends Controller
{
    // GET /api/reports/sales
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

    // GET /api/reports/enrollments
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