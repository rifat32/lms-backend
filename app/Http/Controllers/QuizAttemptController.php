<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


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
     *     path="/quizzes/{id}/attempts",
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
     *         description="Quiz attempt submitted",
     *         @OA\JsonContent(
     *             @OA\Property(property="attempt_id", type="integer", example=1),
     *             @OA\Property(property="score", type="number", example=80),
     *             @OA\Property(property="is_passed", type="boolean", example=true),
     *             @OA\Property(
     *                 property="feedback",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer", example=5),
     *                     @OA\Property(property="user_answer", type="string", example="My essay answer"),
     *                     @OA\Property(property="message", type="string", example="Requires manual grading")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, $id)
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
            $question = Question::findOrFail($answer['question_id']);

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
            'attempt_id' => $quiz_attempt->id,
            'score' => $quiz_attempt->score,
            'is_passed' => $quiz_attempt->is_passed,
            'feedback' => $feedback,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/quiz-attempts/{id}/grade",
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
     *             @OA\Property(property="attempt_id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Grade updated."),
     *             @OA\Property(property="new_score", type="number", example=90)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quiz attempt or question not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function grade(Request $request, $id)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,question_id',
            'score' => 'required|numeric|min:0'
        ]);

        $quiz_attempt = QuizAttempt::findOrFail($id);

        // Add manual score to existing score
        $quiz_attempt->score += $request->score;
        $quiz_attempt->save();

        return response()->json([
            'attempt_id' => $quiz_attempt->id,
            'message' => 'Grade updated.',
            'new_score' => $quiz_attempt->score
        ]);
    }
}
