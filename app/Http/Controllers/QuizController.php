<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Models\Sectionable;
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
     *     summary="Get quiz details with questions and options (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Quiz ID",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *     @OA\Parameter(
     *         name="is_randomized",
     *         in="query",
     *         required=false,
     *         description="Whether to randomize the quiz questions",
     *         @OA\Schema(type="boolean", example="")
     *     ),
     *     @OA\Parameter(
     *         name="question_limit",
     *         in="query",
     *         required=false,
     *         description="The maximum number of questions to include in the quiz",
     *         @OA\Schema(type="integer", example="")
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

        // CHECKING PERMISSION
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        // GETTING QUIZ BY ID
        $quiz = Quiz::with(['questions.options'])->findOrFail($id);

        // GETTING QUESTIONS
        $questions = $quiz->questions;

        // Handle randomization and limit
        if ($quiz->is_randomized && $quiz->question_limit > 0) {
            // Case 1: Randomize + Limit
            $questions = $questions->shuffle()->take($quiz->question_limit);
        } elseif ($quiz->is_randomized) {
            // Case 2: Randomize only
            $questions = $questions->shuffle();
        } elseif ($quiz->question_limit > 0) {
            // Case 3: Limit only
            $questions = $questions->take($quiz->question_limit);
        }

        // $result = [
        //     'id' => $quiz->id,
        //     'title' => $quiz->title,
        //     'questions' => $questions->map(function ($question) {
        //         return [
        //             'question_id' => $question->id,
        //             'text' => $question->question_text,
        //             'type' => $question->question_type,
        //             'options' => $question->options->map(function ($option) {
        //                 return [
        //                     'option_id' => $option->id,
        //                     'text' => $option->option_text,
        //                 ];
        //             }),
        //         ];
        //     })->values(),
        // ];

        // ADD QUESTIONS DATA
        $quiz->setRelation('questions', $questions->values());

        // Return the response
        return response()->json([
            'success' => true,
            'message' => 'Quiz retrieved successfully',
            'data' => $quiz
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/v1.0/quizzes",
     *     operationId="getQuizWithQuestions",
     *     tags={"Quizzes"},
     *     summary="Get quiz details with questions and options (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="is_randomized",
     *         in="query",
     *         required=false,
     *         description="Whether to randomize the quiz questions",
     *         @OA\Schema(type="boolean", example="")
     *     ),
     *     @OA\Parameter(
     *         name="question_limit",
     *         in="query",
     *         required=false,
     *         description="The maximum number of questions to include in the quiz",
     *         @OA\Schema(type="integer", example="")
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

    public function getQuizWithQuestions(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }


        $query = Quiz::with(['questions.options']);

        $quizzes = retrieve_data($query, 'created_at', 'quizzes');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Sections retrieved successfully',
            'meta' => $quizzes['meta'],
            'data' => $quizzes['data'],
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/v1.0/quizzes",
     *     operationId="createQuiz",
     *     tags={"Quizzes"},
     *     summary="Create a new quiz and attach questions (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="Laravel Basics Quiz"),
     *             @OA\Property(
     *                 property="section_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={}
     *             ),
     *             @OA\Property(property="description", type="string", example="Short description of the quiz"),
     *             @OA\Property(property="time_limit", type="integer", example=2),
     *             @OA\Property(property="time_unit", type="string", example="Hours"),
     *             @OA\Property(property="style", type="string", example="pagination"),
     *             @OA\Property(property="is_randomized", type="boolean", example=true),
     *             @OA\Property(property="show_correct_answer", type="boolean", example=false),
     *             @OA\Property(property="allow_retake_after_pass", type="boolean", example=true),
     *             @OA\Property(property="max_attempts", type="integer", example=4),
     *             @OA\Property(property="points_cut_after_retake", type="integer", example=20),
     *             @OA\Property(property="passing_grade", type="integer", example=50),
     *             @OA\Property(property="question_limit", type="integer", example=10),
     *             @OA\Property(property="question_ids", type="array", @OA\Items(type="integer", example=1))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Quiz created successfully")
     * )
     */
    public function store(QuizRequest $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $request_payload = $request->validated();
        $quiz = Quiz::create($request_payload);

        if (!empty($request_payload['question_ids'])) {
            $quiz->questions()->attach($request_payload['question_ids']);
        }



        foreach ($request_payload['section_ids'] as $section_id) {
            Sectionable::create([
                'section_id' => $section_id,
                'sectionable_id' => $quiz->id,
                'sectionable_type' => Quiz::class,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quiz created successfully',
            'data' => $quiz->load('questions')
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/v1.0/quizzes",
     *     operationId="updateQuiz",
     *     tags={"Quizzes"},
     *     summary="Update a quiz and sync questions (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Quiz Title"),
     *             @OA\Property(
     *                 property="section_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={}
     *             ),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="question_ids", type="array", @OA\Items(type="integer", example=2))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Quiz updated successfully")
     * )
     */
    public function update(QuizRequest $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $request_payload = $request->validated();
        $quiz = Quiz::findOrFail($request_payload['id']);

        $quiz->update($request_payload);

        if (isset($request_payload['question_ids'])) {
            $quiz->questions()->sync($request_payload['question_ids']); // sync instead of attach
        }


        // Sectionable::where('sectionable_id', $quiz->id)
        //     ->where('sectionable_type', Quiz::class)
        //     ->delete();
        // foreach ($request_payload['section_ids'] as $section_id) {
        //     Sectionable::create([
        //         'section_id' => $section_id,
        //         'sectionable_id' => $quiz->id,
        //         'sectionable_type' => Quiz::class,
        //     ]);
        // }

        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully',
            'data' => $quiz->load('questions')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/quizzes/{id}",
     *     operationId="deleteQuiz",
     *     tags={"Quizzes"},
     *     summary="Delete a quiz (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Quiz deleted successfully")
     * )
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $quiz = Quiz::findOrFail($id);

        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz deleted successfully'
        ]);
    }
}
