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

        $result = [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'questions' => $quiz->questions->map(function ($question) {
                return [
                    'question_id' => $question->id,
                    'text' => $question->question_text,
                    'type' => $question->question_type,
                    'options' => $question->options->map(function ($option) {
                        return [
                            'option_id' => $option->id,
                            'text' => $option->option_text
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Quiz retrieved successfully',
            'data' => $result
        ], 200);
    }
}
