<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;


/**
 * @OA\Tag(
 *     name="Quizzes",
 *     description="Endpoints to get quizzes with questions and options"
 * )
 */
class QuizController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1.0/quizzes/{id}",
     *     operationId="getQuizWithQuestionsById",
     *     tags={"Quizzes"},
     *     summary="Get quiz details with questions and options",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Quiz ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="is_randomized",
     *         in="query",
     *         required=false,
     *         description="Whether to randomize the quiz questions",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="question_limit",
     *         in="query",
     *         required=false,
     *         description="The maximum number of questions to include in the quiz",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quiz retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Quiz retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Laravel Basics Quiz"),
     *                 @OA\Property(
     *                     property="questions",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="question_id", type="integer", example=10),
     *                         @OA\Property(property="text", type="string", example="What is Laravel?"),
     *                         @OA\Property(property="type", type="string", example="multiple_choice"),
     *                         @OA\Property(
     *                             property="options",
     *                             type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="option_id", type="integer", example=101),
     *                                 @OA\Property(property="text", type="string", example="A PHP framework")
     *                             )
     *                         )
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
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quiz not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quiz not found.")
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

    public function getQuizWithQuestionsById($id)
    {
        $quiz = Quiz::with(['questions.options'])->find($id);

        if (empty($quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }

        $questions = $quiz->questions;

        // Handle randomization and limit
        if ($quiz->is_randomized && $quiz->question_limit > 0) {
            // Case 3: Randomize + Limit
            $questions = $questions->shuffle()->take($quiz->question_limit);
        } elseif ($quiz->is_randomized) {
            // Case 1: Randomize only
            $questions = $questions->shuffle();
        } elseif ($quiz->question_limit > 0) {
            // Case 2: Limit only
            $questions = $questions->take($quiz->question_limit);
        }

        $result = [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'questions' => $questions->map(function ($question) {
                return [
                    'question_id' => $question->id,
                    'text' => $question->question_text,
                    'type' => $question->question_type,
                    'options' => $question->options->map(function ($option) {
                        return [
                            'option_id' => $option->id,
                            'text' => $option->option_text,
                        ];
                    }),
                ];
            })->values(), // reset keys
        ];

        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Quiz retrieved successfully',
            'data' => $result
        ], 200);
    }
}
