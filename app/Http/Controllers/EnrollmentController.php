<?php

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    // POST /api/enrollments
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
        ]);

        $user = Auth::user();

        // Prevent duplicate enrollment
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $request->course_id],
            ['enrolled_at' => now()]
        );

        return response()->json($enrollment, 201);
    }

    // GET /api/users/{id}/enrollments
    public function userEnrollments($id)
    {
        $enrollments = Enrollment::with('course')
            ->where('user_id', $id)
            ->get();

        $data = $enrollments->map(function($enrollment) {
            return [
                'enrollment_id' => $enrollment->id,
                'course' => [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'description' => $enrollment->course->description,
                    'price' => $enrollment->course->price,
                ],
                'progress' => $enrollment->progress,
                'status' => $enrollment->status,
                'enrolled_at' => $enrollment->enrolled_at,
            ];
        });

        return response()->json($data);
    }
}
