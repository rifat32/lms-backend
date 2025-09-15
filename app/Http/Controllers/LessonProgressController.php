<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonProgressController extends Controller
{
    // PUT /api/lessons/{id}/progress
    public function update(Request $request, $id)
    {
        $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        $user = Auth::user();

        $lesson = Lesson::findOrFail($id);

        // Ensure the user is enrolled in the course
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->firstOrFail();

        // Update progress (for simplicity, mark completed = 100%)
        $enrollment->progress = $request->is_completed ? 100 : $enrollment->progress;
        $enrollment->save();

        return response()->json([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'progress_status' => $request->is_completed ? 'completed' : 'in_progress',
        ]);
    }
}
