<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


/**
 * @OA\Tag(
 *     name="LessonProgress",
 *     description="Endpoints to update lesson progress for enrolled users"
 * )
 */
class LessonProgressController extends Controller
{
    /**
     * @OA\Put(
     *     path="lessons/{id}/progress",
     *     tags={"LessonProgress"},
     *     summary="Update lesson progress for authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lesson ID",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_completed"},
     *             @OA\Property(property="is_completed", type="boolean", example=true, description="Mark lesson as completed or not")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson progress updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="lesson_id", type="integer", example=5),
     *             @OA\Property(property="progress_status", type="string", example="completed")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Lesson or enrollment not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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
