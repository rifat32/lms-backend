<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Question;
use App\Models\Quiz;
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
 *     path="/v1.0/quiz-attempts/grade",
 *     operationId="gradeQuizAttempt",
 *     tags={"QuizAttempts"},
 *     summary="Manually grade a quiz attempt (role: Admin, Owner, or Lecturer only)",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"attempt_id", "question_id", "score"},
 *             @OA\Property(property="attempt_id", type="integer", example=1, description="Quiz Attempt ID"),
 *             @OA\Property(property="question_id", type="integer", example=5, description="Question ID to grade"),
 *             @OA\Property(property="score", type="number", example=10, description="Score assigned for the question or attempt")
 *         )
 *     ),
 *
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
public function gradeQuizAttempt(Request $request)
{
    try {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        DB::beginTransaction();

        $request->validate([
            'attempt_id' => 'required|integer|exists:quiz_attempts,id',
            'question_id' => ['required', 'integer', new ValidQuestion()],
            'score' => 'required|numeric|min:0'
        ]);

        $quiz_attempt = QuizAttempt::find($request->attempt_id);

        if (empty($quiz_attempt)) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz attempt not found',
            ], 404);
        }

        // Update manual score
        $quiz_attempt->score = $request->score;
        $quiz_attempt->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Grade updated.',
            'data' => [
                'attempt_id' => $quiz_attempt->id,
                'new_score' => $quiz_attempt->score
            ]
        ], 200);
    } catch (\Throwable $th) {
        DB::rollBack();
        throw $th;
    }
}



/**
 * @OA\Post(
 *     path="/v1.0/quizzes/attempts/start",
 *     operationId="startQuizAttempt",
 *     tags={"QuizAttempts"},
 *     summary="Start a quiz attempt for authenticated user (role: Student only)",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"quiz_id"},
 *             @OA\Property(property="quiz_id", type="integer", example=5, description="ID of the quiz to start attempt for")
 *         )
 *     ),
 *
 *     @OA\Response(response=201, description="Quiz attempt started"),
 *     @OA\Response(response=400, description="User already has an active attempt or invalid quiz ID"),
 *     @OA\Response(response=401, description="Unauthorized or user not a student"),
 * )
 */
public function startQuizAttempt(Request $request)
{
    if (!auth()->user()->hasAnyRole(['student'])) {
        return response()->json([
            "message" => "You can not perform this action"
        ], 401);
    }

    $request->validate([
        'quiz_id' => 'required|integer|exists:quizzes,id',
    ]);

    $user = Auth::user();
    $quiz = Quiz::findOrFail($request->quiz_id);

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
            'time_limit' => $quiz->time_limit,
            'expires_at' => now()->addMinutes($quiz->time_limit)->toIso8601String(),
        ]
    ], 201);
}

   /**
 * @OA\Post(
 *     path="/v1.0/quizzes/attempts/submit",
 *     operationId="submitQuizAttempt",
 *     tags={"QuizAttempts"},
 *     summary="Submit a quiz attempt for authenticated user (role: Student only)",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"quiz_id", "answers"},
 *             @OA\Property(property="quiz_id", type="integer", example=5, description="ID of the quiz being submitted"),
 *             @OA\Property(
 *                 property="answers",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"question_id", "user_answer"},
 *                     @OA\Property(property="question_id", type="integer", example=12, description="ID of the question"),
 *                     @OA\Property(property="user_answer", type="string", example="OptionA", description="User's answer (option ID or text)")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=201, description="Quiz attempt submitted successfully"),
 *     @OA\Response(response=400, description="Invalid input or already submitted"),
 *     @OA\Response(response=401, description="Unauthorized or user not a student"),
 *     @OA\Response(response=403, description="Time is up! Quiz attempt expired"),
 * )
 */
public function submitQuizAttempt(Request $request)
{
    if (!auth()->user()->hasAnyRole(['student'])) {
        return response()->json([
            "message" => "You can not perform this action"
        ], 401);
    }

    $request->validate([
        'quiz_id' => 'required|integer|exists:quizzes,id',
        'answers' => 'required|array',
        'answers.*.question_id' => 'required|exists:questions,id',
        'answers.*.user_answer' => 'required'
    ]);

    $user = Auth::user();
    $quiz = Quiz::findOrFail($request->quiz_id);

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

    if ($attempt->is_passed) {
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
