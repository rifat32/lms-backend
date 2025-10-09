<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Question;
use App\Rules\ValidQuestion;
use App\Utils\BasicUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


/**
 * @OA\Tag(
 *     name="QuizAttempts",
 *     description="Endpoints to attempt quizzes and grade essays (Admin)"
 * )
 */
class QuizAttemptController extends Controller
{
    
    use BasicUtil;
    /**
     * @OA\Put(
     *     path="/v1.0/quiz-attempts/{id}/grade",
     *     operationId="gradeQuizAttempt",
     *     tags={"QuizAttempts"},
     *     summary="Manually grade a quiz attempt (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Quiz Attempt ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_id","score"},
     *             @OA\Property(property="question_id", type="integer", example=5),
     *             @OA\Property(property="score", type="number", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Grade updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Grade updated."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="attempt_id", type="integer", example=1),
     *                 @OA\Property(property="message", type="string", example="Grade updated."),
     *                 @OA\Property(property="new_score", type="number", example=90)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request parameters.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quiz attempt or question not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quiz attempt not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The score field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="score", type="array",
     *                     @OA\Items(type="string", example="The score field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function gradeQuizAttempt(Request $request, $id)
    {
        try {
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            // Begin transaction
            DB::beginTransaction();

            // Validate the request
            $request->validate([
                'question_id' => ['required', 'integer', new ValidQuestion()],
                'score' => 'required|numeric|min:0'
            ]);

            $quiz_attempt = QuizAttempt::find($id);

            if (empty($quiz_attempt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found',
                ], 404);
            }

            // Add manual score to existing score
            $quiz_attempt->score = $request->score;
            $quiz_attempt->save();

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Grade updated.',
                'data' => [
                    'attempt_id' => $quiz_attempt->id,
                    'message' => 'Grade updated.',
                    'new_score' => $quiz_attempt->score
                ]
            ], 200);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }



 /**
     * @OA\Post(
     *     path="/v1.0/quizzes/{id}/attempts/start",
     *     operationId="startQuizAttempt",
     *     tags={"QuizAttempts"},
     *     summary="Start a quiz attempt for authenticated user (role: Student only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Quiz attempt started")
     * )
     */
    public function startQuizAttempt($id)
    {
        if (!auth()->user()->hasAnyRole(['student'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $user = Auth::user();
        $quiz = Quiz::findOrFail($id);

        // check if already has active attempt
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->first();

        if ($attempt) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active attempt.',
                'attempt_id' => $attempt->id,
            ], 400);
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 0,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz attempt started',
            'data' => [
                'attempt_id' => $attempt->id,
                'time_limit' => $quiz->time_limit, // minutes
                'expires_at' => now()->addMinutes($quiz->time_limit)->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/v1.0/quizzes/{id}/attempts/submit",
     *     operationId="submitQuizAttempt",
     *     tags={"QuizAttempts"},
     *     summary="Submit a quiz attempt for authenticated user (role: Student only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Quiz attempt submitted")
     * )
     */
    public function submitQuizAttempt(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['student'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.user_answer' => 'required'
        ]);

        $user = Auth::user();
        $quiz = Quiz::findOrFail($id);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->firstOrFail();

        // ⏱️ enforce timer
        $elapsed = now()->diffInSeconds($attempt->started_at);
        $limit_seconds = $quiz->time_limit * 60;

        if ($elapsed > $limit_seconds) {
            $attempt->is_expired = true;
            $attempt->completed_at = now();
            $attempt->save();

            return response()->json([
                'success' => false,
                'message' => 'Time is up! Quiz attempt expired.',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'elapsed' => $elapsed,
                    'time_limit' => $limit_seconds,
                ]
            ], 403);
        }

        // ✅ proceed with scoring
        $score = 0;
        $feedback = [];

        foreach ($request->answers as $answer) {
            $question = Question::find($answer['question_id']);
            if (!$question) {
                continue;
            }

            if ($question->question_type !== 'essay') {
                $correct = $question->options()->where('is_correct', true)->first();
                $is_correct = $correct && $correct->id == $answer['user_answer'];
                $score += $is_correct ? $question->points : 0;
            } else {
                $feedback[] = [
                    'question_id' => $question->id,
                    'user_answer' => $answer['user_answer'],
                    'message' => 'Requires manual grading',
                ];
            }
        }

        $attempt->score = $score;
        $attempt->is_passed = $score >= 50;
        $attempt->completed_at = now();
        $attempt->time_spent = $elapsed;
        $attempt->save();

        if($attempt->is_passed){
          $this->recalculateCourseProgress($user->id, $quiz->course_id);
        }


        return response()->json([
            'success' => true,
            'message' => 'Quiz attempt submitted',
            'data' => [
                'attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'is_passed' => $attempt->is_passed,
                'time_spent' => $attempt->time_spent,
                'feedback' => $feedback,
            ]
        ], 201);
    }







    
}
