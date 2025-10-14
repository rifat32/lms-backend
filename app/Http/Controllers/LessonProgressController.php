<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonTimeRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Rules\ValidCourse;
use App\Rules\ValidLesson;
use App\Utils\BasicUtil;
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
    use BasicUtil;

    /**
     * @OA\Put(
     *     path="/v1.0/lessons/progress",
     *     operationId="updateLessonProgress",
     *     tags={"LessonProgress"},
     *     summary="Update lesson progress for authenticated user (role: Student only)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id", "lesson_id","is_completed"},
     *             @OA\Property(
     *                 property="course_id",
     *                 type="integer",
     *                 example="",
     *                 description="Course ID to update progress for"
     *             ),
     *             @OA\Property(
     *                 property="lesson_id",
     *                 type="integer",
     *                 example="",
     *                 description="Lesson ID to update progress for"
     *             ),
     *             @OA\Property(
     *                 property="is_completed",
     *                 type="boolean",
     *                 example="",
     *                 description="Mark lesson as completed or not"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lesson progress updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson progress updated"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="lesson_id", type="integer", example=5),
     *                 @OA\Property(property="course_id", type="integer", example=5),
     *                 @OA\Property(property="progress_status", type="string", example="completed")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Invalid request payload"))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized access"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="You do not have permission to update this lesson progress"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson or enrollment not found",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Lesson not found or user not enrolled"))
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Lesson progress already marked as completed"))
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
    public function updateLessonProgress(Request $request)
    {
        DB::beginTransaction();

        try {

            // CHECK PERMISSION
            if (!auth()->user()->hasAnyRole(['student'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            // VALIDATE REQUEST
            $request->validate([
                'lesson_id' => ['required', 'integer', new ValidLesson()],
                'course_id' => ['required', 'integer', new ValidCourse()],
                'is_completed' => 'required|boolean',
            ]);

            // Get the authenticated user
            $user = Auth::user();
            // Get the lesson
            $lesson = Lesson::findOrFail($request->lesson_id);
            // Get the course
            $course = Course::findOrFail($request->course_id);


            // Ensure the user is enrolled in the course
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->firstOrFail();

            // Update progress (for simplicity, mark completed = 100%)
            $enrollment->progress = $request->is_completed ? 100 : $enrollment->progress;
            $enrollment->save();

            $this->recalculateCourseProgress($user->id, $course->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson progress updated',
                'data' => [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                    'progress_status' => $request->is_completed ? 'completed' : 'in_progress',
                ]
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }





    /**
     * @OA\Put(
     *     path="/v1.0/lessons/time",
     *     operationId="trackLessonTime",
     *     tags={"LessonProgress"},
     *     summary="Track lesson time (start / stop / heartbeat) for authenticated user (role: Student only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id", "lesson_id","event"},
     *             @OA\Property(
     *                 property="course_id",
     *                 type="integer",
     *                 example="",
     *                 description="Course ID to update progress for"
     *             ),
     *             @OA\Property(
     *                 property="lesson_id", 
     *                 type="integer", 
     *                 example="", 
     *                 description="Lesson ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson time tracked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson time tracked"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="lesson_id", type="integer", example=5),
     *                 @OA\Property(property="total_time_spent", type="integer", example=3600, description="seconds"),
     *                 @OA\Property(property="is_completed", type="boolean", example=false),
     *                 @OA\Property(property="last_accessed", type="string", format="date-time", example="2025-10-02T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Lesson not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function trackLessonTime(LessonTimeRequest $request)
    {
        DB::beginTransaction();
        try {
            if (!auth()->user()->hasAnyRole(['student'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            // VALIDATE REQUEST
            $request->validate([
                'lesson_id' => ['required', 'integer', new ValidLesson()],
                'course_id' => ['required', 'integer', new ValidCourse()],
            ]);

            // GET AUTHENTICATED USER
            $user = Auth::user();

            // GET LESSON
            $lesson = Lesson::findOrFail($request->lesson_id);
            $course = Course::findOrFail($request->course_id);

            // create or fetch progress row
            $progress = LessonProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'total_time_spent' => 0,
                    'is_completed' => false
                ]
            );

            // increment 1 minute (60 seconds) on each API call
            $progress->increment('total_time_spent', 60);

            // update last_accessed
            $progress->last_accessed = now();
            $progress->save();

            // auto-complete if lesson duration defined (lesson->duration expected in minutes)
            $lesson_duration_seconds = ($lesson->duration ?? 0) * 60;
            if ($lesson_duration_seconds > 0 && $progress->total_time_spent >= $lesson_duration_seconds && !$progress->is_completed) {
                $progress->is_completed = true;
                $progress->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson time tracked',
                'data' => [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'lesson_id' => $lesson->id,
                    'total_time_spent' => $progress->total_time_spent,
                    'is_completed' => $progress->is_completed,
                    'last_accessed' => optional($progress->last_accessed)->toIso8601String(),
                ],
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    // public function trackLessonTime(LessonTimeRequest $request, $id)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $user = Auth::user();
    //         $data = $request->validated(); // <-- using validated data
    //         $event = $data['event'];

    //         $lesson = Lesson::findOrFail($id);

    //         // create or fetch progress row
    //         $progress = LessonProgress::firstOrCreate(
    //             ['user_id' => $user->id, 'lesson_id' => $lesson->id],
    //             ['total_time_spent' => 0, 'is_completed' => false]
    //         );

    //         if ($event === 'start') {
    //             // avoid creating duplicate open sessions
    //             $open = LessonSession::where('user_id', $user->id)
    //                 ->where('lesson_id', $lesson->id)
    //                 ->whereNull('end_time')
    //                 ->exists();

    //             if (! $open) {
    //                 LessonSession::create([
    //                     'user_id' => $user->id,
    //                     'lesson_id' => $lesson->id,
    //                     'start_time' => now(),
    //                 ]);
    //             }

    //             // update last_accessed
    //             $progress->last_accessed = now();
    //             $progress->save();
    //         }

    //         if ($event === 'stop') {
    //             $session = LessonSession::where('user_id', $user->id)
    //                 ->where('lesson_id', $lesson->id)
    //                 ->whereNull('end_time')
    //                 ->latest()
    //                 ->first();

    //             if ($session) {
    //                 $session->end_time = now();
    //                 // $session->start_time is cast to datetime so diffInSeconds works
    //                 $session->duration = $session->end_time->diffInSeconds($session->start_time);
    //                 $session->save();

    //                 // increment safely
    //                 $progress->increment('total_time_spent', $session->duration);
    //                 $progress->last_accessed = now();
    //                 $progress->save();
    //             }
    //         }

    //         if ($event === 'heartbeat') {
    //             // If there's no open session, create one to avoid missing time
    //             $session = LessonSession::where('user_id', $user->id)
    //                 ->where('lesson_id', $lesson->id)
    //                 ->whereNull('end_time')
    //                 ->latest()
    //                 ->first();

    //             if (!$session) {
    //                 LessonSession::create([
    //                     'user_id' => $user->id,
    //                     'lesson_id' => $lesson->id,
    //                     'start_time' => now(),
    //                 ]);
    //             }

    //             $progress->last_accessed = now();
    //             $progress->save();
    //         }

    //         // Auto-complete if lesson duration defined (lesson->duration expected to be minutes)
    //         $lesson_duration_seconds = ($lesson->duration ?? 0) * 60;
    //         $progress->refresh(); // reload latest totals
    //         if ($lesson_duration_seconds > 0 && $progress->total_time_spent >= $lesson_duration_seconds && ! $progress->is_completed) {
    //             $progress->is_completed = true;
    //             $progress->save();

    //             // optionally: update Enrollment progress here (if you have Enrollment model logic)
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Lesson time tracked',
    //             'data' => [
    //                 'user_id' => $user->id,
    //                 'lesson_id' => $lesson->id,
    //                 'total_time_spent' => $progress->total_time_spent,
    //                 'is_completed' => $progress->is_completed,
    //                 'last_accessed' => optional($progress->last_accessed)->toIso8601String(),
    //             ],
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         DB::rollBack();
    //         throw $th;
    //     }
    // }






}
