<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttemptAnswer;
use App\Rules\ValidCourse;
use App\Rules\ValidQuestion;
use App\Rules\ValidQuiz;
use App\Utils\BasicUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

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
     *             @OA\Property(
     *             property="quiz_id",
     *             type="integer",
     *             example="",
     *             description="ID of the quiz to start attempt for"),
     *  *             @OA\Property(
     *             property="course_id",
     *             type="integer",
     *             example="",
     *             description="ID of the course to start attempt for")
     *
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

        // CHECK PERMISSION
        if (!auth()->user()->hasAnyRole(['student'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        // VALIDATE REQUEST
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            "course_id" => "required|integer"
        ]);

        // GET AUTHENTICATED USER
        $user = Auth::user();

        // GET QUIZ
        $quiz = Quiz::findOrFail($request->quiz_id);

        // check if already has active attempt
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->first();

        if ($attempt) {
            throw new BadRequestException("You already have an active attempt.");
            // return response()->json([
            //     'success' => false,
            //     'message' => 'You already have an active attempt.',
            //     'attempt_id' => $attempt->id,
            // ], 400);
        }

        // DETERMINE TIME LIMIT BASED ON UNIT
        $time_limit = $quiz->time_limit ?? 0;
        $expires_at = null;

        if ($quiz->time_unit === Quiz::TIME_UNITS['HOURS']) {
            $expires_at = now()->addHours($time_limit);
        } elseif ($quiz->time_unit === Quiz::TIME_UNITS['MINUTES']) {
            $expires_at = now()->addMinutes($time_limit);
        } else {
            // default fallback (minutes)
            $expires_at = now()->addMinutes($time_limit);
        }



        // CREATE QUIZ ATTEMPT
        $attempt = QuizAttempt::create([
            "course_id" => $request->course_id,
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 0,
            'total_points' => 0,
            'started_at' => now(),
        ]);

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Quiz attempt started',
            'data' => [
                'attempt_id' => $attempt->id,
                'time_limit' => $quiz->time_limit,
                'time_unit' => $quiz->time_unit,
                'expires_at' => $expires_at->toIso8601String(),
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
     *             required={"course_id","quiz_id", "answers"},
     *
     *  *             @OA\Property(
     *                 property="course_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the course"
     *             ),
     *             @OA\Property(
     *                 property="quiz_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the quiz being submitted"
     *             ),
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"question_id", "user_answer_ids"},
     *                     @OA\Property(
     *                         property="question_id",
     *                         type="integer",
     *                         example=12,
     *                         description="ID of the question"
     *                     ),
     *                     @OA\Property(
     *                         property="user_answer_ids",
     *                         type="array",
     *                         @OA\Items(type="integer"),
     *                         example={1,3},
     *                         description="Array of user's selected answer IDs. Multiple IDs allowed for multi-choice questions"
     *                     )
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
        // CHECK PERMISSION
        if (!auth()->user()->hasAnyRole(['student'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        // VALIDATE PAYLOAD (use array rule format)
        $request->validate([
            'course_id' => ['required', 'numeric', new ValidCourse()],
            'quiz_id' => ['required', 'integer', new ValidQuiz()],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', new ValidQuestion()],
            'answers.*.user_answer_ids' => ['required', 'array'],
        ]);

        $user = Auth::user();
        $quiz = Quiz::findOrFail($request->quiz_id);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('course_id', $request->course_id)
            ->whereNull('completed_at')
            ->firstOrFail();

        // ⏱️ enforce timer
        $elapsed = now()->diffInSeconds($attempt->started_at);
        $limit_seconds = $quiz->time_unit === Quiz::TIME_UNITS['HOURS']
            ? $quiz->time_limit * 3600
            : $quiz->time_limit * 60;

        if ($elapsed > $limit_seconds) {
            $attempt->is_expired = true;
            $attempt->completed_at = now();
            $attempt->time_spent = $elapsed;
            $attempt->save();

            // Count attempts (include this expired one)
            $attempts_count = QuizAttempt::where('quiz_id', $request->quiz_id)
                ->where('user_id', $user->id)
                ->where('course_id', $request->course_id)
                ->count();

            // Recalculate course progress even on timeout (per your request)
            $percentage = $this->recalculateCourseProgress($request->course_id);

            return response()->json([
                'success' => false,
                'message' => 'Time is up! Quiz attempt expired.',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'elapsed' => $elapsed,
                    'time_limit' => $limit_seconds,
                    'time_unit' => $quiz->time_unit,
                    'attempts_count' => $attempts_count,
                    'course_progress_percentage' => $percentage,
                ]
            ], 406);
        }

        // ✅ SCORING
        $score = 0;
        $total_points = 0;
        $feedback = [];

        foreach ($request->answers as $answer) {
            $question = Question::find($answer['question_id']);
            if (!$question) {
                continue;
            }

            $total_points += $question->points;

            if ($question->question_type !== 'essay') {
                // GET all correct answer IDs
                $correct_ids = $question->options()->where('is_correct', true)->pluck('id')->toArray();
                $user_ids = $answer['user_answer_ids'];

                // check if user selected exactly all correct answers (no more, no less)
                sort($correct_ids);
                sort($user_ids);
                $is_correct = $correct_ids === $user_ids;

                // ✅ Save answer details
                QuizAttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'user_answer_ids' => $user_ids,
                    'correct_answer_ids' => $correct_ids,
                    'is_correct' => $is_correct,
                ]);

                $score += $is_correct ? $question->points : 0;
            } else {
                $feedback[] = [
                    'question_id' => $question->id,
                    'user_answer_ids' => $answer['user_answer_ids'],
                    'message' => 'Requires manual grading',
                ];
            }
        }

        // 📊 Calculate percentage
        $percentage_score = $total_points > 0 ? ($score / $total_points) * 100 : 0;

        $attempt->total_points = $total_points;
        $attempt->score = $score;
        $attempt->is_passed = $percentage_score >= $quiz->passing_grade; // use quiz passing_grade
        $attempt->completed_at = now();
        $attempt->time_spent = $elapsed;
        $attempt->save();

        // Always recalculate course progress (pass or fail). This ensures progress updates
        // when the user fails as well, like you requested.
        $percentage = $this->recalculateCourseProgress($request->course_id);

        // Get attempt count for this user and quiz
        $attempts_count = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->count();

        // Add attempts count to attempt object for response convenience
        $attempt->attempts_count = $attempts_count;

        // return response
        return response()->json([
            'success' => true,
            'message' => 'Quiz attempt submitted',
            'data' => $attempt->load(['quiz_attempt_answers', 'quiz']),
            'course_progress_percentage' => $percentage,
            'feedback' => $feedback, // include essays needing manual grading info if any
        ], 201);
    }
}
