<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Question;
use App\Rules\ValidQuestion;
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
    /**
     * @OA\Post(
     *     path="/v1.0/quizzes/{id}/attempts",
     *     operationId="submitQuizAttempt",
     *     tags={"QuizAttempts"},
     *     summary="Submit a quiz attempt for authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Quiz ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer", example=10),
     *                     @OA\Property(property="user_answer", type="string", example="A")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Quiz attempt submitted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz attempt submitted"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="attempt_id", type="integer", example=1),
     *                 @OA\Property(property="score", type="number", example=80),
     *                 @OA\Property(property="is_passed", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="feedback",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="question_id", type="integer", example=5),
     *                         @OA\Property(property="user_answer", type="string", example="My essay answer"),
     *                         @OA\Property(property="message", type="string", example="Requires manual grading")
     *                     )
     *                 )
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to submit this quiz.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quiz or question not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Question not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The answers field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="answers", type="array",
     *                     @OA\Items(type="string", example="The answers field is required.")
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

    public function submitQuizAttempt(Request $request, $id)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,question_id',
            'answers.*.user_answer' => 'required'
        ]);

        $user = Auth::user();

        $quiz_attempt = QuizAttempt::create([
            'quiz_id' => $id,
            'user_id' => $user->id,
            'score' => 0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $score = 0;
        $feedback = [];

        foreach ($request->answers as $answer) {
            $question = Question::find($answer['question_id']);

            if (empty($question)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found',
                ], 404);
            }

            $is_correct = false;
            if ($question->question_type !== 'essay') {
                // For non-essay, check if answer matches correct option
                $correct_option = $question->options()->where('is_correct', true)->first();
                $is_correct = $correct_option && $correct_option->id == $answer['user_answer'];
                $score += $is_correct ? $question->points : 0;
            } else {
                $feedback[] = [
                    'question_id' => $question->question_id,
                    'user_answer' => $answer['user_answer'],
                    'message' => 'Requires manual grading'
                ];
            }
        }

        $quiz_attempt->score = $score;
        $quiz_attempt->is_passed = $score >= 50; // example passing score
        $quiz_attempt->save();

        return response()->json([
            'success' => true,
            'message' => 'Quiz attempt submitted',
            'data' => [
                'attempt_id' => $quiz_attempt->id,
                'score' => $quiz_attempt->score,
                'is_passed' => $quiz_attempt->is_passed,
                'feedback' => $feedback,
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/v1.0/quiz-attempts/{id}/grade",
     *     operationId="gradeQuizAttempt",
     *     tags={"QuizAttempts"},
     *     summary="Manually grade a quiz attempt (Admin only)",
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
            $quiz_attempt->score += $request->score;
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
}
