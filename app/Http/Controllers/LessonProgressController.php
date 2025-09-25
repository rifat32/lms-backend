<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     *     path="/v1.0/lessons/{id}/progress",
     *     operationId="updateLessonProgress",
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
     *             @OA\Property(
     *                 property="is_completed",
     *                 type="boolean",
     *                 example=true,
     *                 description="Mark lesson as completed or not"
     *             )
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
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request payload")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to update this lesson progress")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson or enrollment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found or user not enrolled")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson progress already marked as completed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The is_completed field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(
     *                     property="is_completed",
     *                     type="array",
     *                     @OA\Items(type="string", example="The is_completed field must be true or false.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function updateLessonProgress(Request $request, $id)
    {
        try {
            // Begin transaction
            DB::beginTransaction();

            // Validate the request
            $request->validate([
                'is_completed' => 'required|boolean',
            ]);

            $user = Auth::user();

            // Ensure the lesson exists
            $lesson = Lesson::findOrFail($id);

            // Ensure the user is enrolled in the course
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $lesson->course_id)
                ->firstOrFail();

            // Update progress (for simplicity, mark completed = 100%)
            $enrollment->progress = $request->is_completed ? 100 : $enrollment->progress;
            $enrollment->save();

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Lesson progress updated',
                'data' => [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'progress_status' => $request->is_completed ? 'completed' : 'in_progress',
                ]
            ]);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }
}
