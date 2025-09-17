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
     *     path="quizzes/{id}",
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
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Laravel Basics Quiz"),
     *             @OA\Property(
     *                 property="questions",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer", example=10),
     *                     @OA\Property(property="text", type="string", example="What is Laravel?"),
     *                     @OA\Property(property="type", type="string", example="multiple_choice"),
     *                     @OA\Property(
     *                         property="options",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="option_id", type="integer", example=101),
     *                             @OA\Property(property="text", type="string", example="A PHP framework")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Quiz not found")
     * )
     */
    public function show($id)
    {
        $quiz = Quiz::with(['questions.options'])->findOrFail($id);

        return response()->json([
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
        ]);
    }
}
