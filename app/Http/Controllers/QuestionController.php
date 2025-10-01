<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/v1.0/questions",
     *     operationId="createQuestion",
     *     tags={"question_management.question"},
     *     summary="Create a new question",
     *     description="Creates a new question for a specific quiz.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"quiz_id", "question_text", "question_type", "points"},
     *             @OA\Property(
     *                 property="quiz_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the quiz this question belongs to"
     *             ),
     *             @OA\Property(
     *                 property="question_text",
     *                 type="string",
     *                 maxLength=255,
     *                 example="What is the capital of France?",
     *                 description="The text of the question"
     *             ),
     *             @OA\Property(
     *                 property="question_type",
     *                 type="string",
     *                 enum={"mcq", "true_false", "short_answer"},
     *                 example="mcq",
     *                 description="Type of the question"
     *             ),
     *             @OA\Property(
     *                 property="points",
     *                 type="integer",
     *                 minimum=1,
     *                 example=5,
     *                 description="Number of points awarded for the question"
     *             ),
     *             @OA\Property(
     *                 property="time_limit",
     *                 type="integer",
     *                 nullable=true,
     *                 minimum=0,
     *                 example=30,
     *                 description="Time limit for the question in seconds (nullable)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Question created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="quiz_id", type="integer", example=1),
     *             @OA\Property(property="question_text", type="string", example="What is the capital of France?"),
     *             @OA\Property(property="question_type", type="string", example="mcq"),
     *             @OA\Property(property="points", type="integer", example=5),
     *             @OA\Property(property="time_limit", type="integer", example=30, nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quiz not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Quiz not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="A question with this text already exists for this quiz.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The question_text field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="question_text", type="array",
     *                     @OA\Items(type="string", example="The question_text field is required.")
     *                 ),
     *                 @OA\Property(property="question_type", type="array",
     *                     @OA\Items(type="string", example="The selected question_type is invalid.")
     *                 ),
     *                 @OA\Property(property="points", type="array",
     *                     @OA\Items(type="string", example="The points must be at least 1.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function createQuestion(QuestionRequest $request)
    {
        try {
            // Begin transaction
            DB::beginTransaction();

            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // CREATE QUESTION
            $question = Question::create($request_payload);

            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'question' => $question
            ], 201);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Put(
     *     path="/v1.0/questions/{id}",
     *     operationId="updateQuestion",
     *     tags={"question_management.question"},
     *     summary="Update an existing question",
     *     description="Updates a question by its ID.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the question to update",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"quiz_id", "question_text", "question_type", "points"},
     *             @OA\Property(
     *                 property="quiz_id",
     *                 type="integer",
     *                 example=1,
     *                 description="ID of the quiz this question belongs to"
     *             ),
     *             @OA\Property(
     *                 property="question_text",
     *                 type="string",
     *                 maxLength=255,
     *                 example="What is the capital of Germany?",
     *                 description="The text of the question"
     *             ),
     *             @OA\Property(
     *                 property="question_type",
     *                 type="string",
     *                 enum={"mcq", "true_false", "short_answer"},
     *                 example="true_false",
     *                 description="Type of the question"
     *             ),
     *             @OA\Property(
     *                 property="points",
     *                 type="integer",
     *                 minimum=1,
     *                 example=10,
     *                 description="Number of points awarded for the question"
     *             ),
     *             @OA\Property(
     *                 property="time_limit",
     *                 type="integer",
     *                 nullable=true,
     *                 minimum=0,
     *                 example=60,
     *                 description="Time limit for the question in seconds (nullable)"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Question updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Question updated successfully"),
     *             @OA\Property(
     *                 property="question",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="quiz_id", type="integer", example=1),
     *                 @OA\Property(property="question_text", type="string", example="What is the capital of Germany?"),
     *                 @OA\Property(property="question_type", type="string", example="true_false"),
     *                 @OA\Property(property="points", type="integer", example=10),
     *                 @OA\Property(property="time_limit", type="integer", nullable=true, example=60),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Question not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="A question with this text already exists for this quiz.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The question_text field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="question_text", type="array",
     *                     @OA\Items(type="string", example="The question_text field is required.")
     *                 ),
     *                 @OA\Property(property="question_type", type="array",
     *                     @OA\Items(type="string", example="The selected question_type is invalid.")
     *                 ),
     *                 @OA\Property(property="points", type="array",
     *                     @OA\Items(type="string", example="The points must be at least 1.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function updateQuestion(QuestionRequest $request)
    {
        try {
            // 
            DB::beginTransaction();
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // UPDATE QUESTION
            $question = Question::findOrFail($request_payload['id']);
            $question->update($request_payload);

            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'question' => $question
            ], 200);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Get(
     *     path="/v1.0/questions",
     *     operationId="getAllQuestions",
     *     tags={"question_management.question"},
     *     summary="Get all questions",
     *     description="Retrieve a list of all questions.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of questions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="quiz_id", type="integer", example=1),
     *                 @OA\Property(property="question_text", type="string", example="What is the capital of France?"),
     *                 @OA\Property(property="question_type", type="string", example="mcq"),
     *                 @OA\Property(property="points", type="integer", example=5),
     *                 @OA\Property(property="time_limit", type="integer", nullable=true, example=30),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No questions found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No questions found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */

    public function getAllQuestions(Request $request)
    {
 // GET ALL QUESTIONS
          $query = Question::query();

        $questions = retrieve_data($query, 'created_at', 'questions');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Questions retrieved successfully',
            'meta' => $questions['meta'],
            'data' => $questions['data'],
        ], 200);

       
       
    }

    /**
     * @OA\Get(
     *     path="/v1.0/questions/{id}",
     *     operationId="getQuestionById",
     *     tags={"question_management.question"},
     *     summary="Get question by ID",
     *     description="Retrieve a specific question by its unique ID.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the question to retrieve",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Question retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Question retrieved successfully"),
     *             @OA\Property(
     *                 property="question",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="quiz_id", type="integer", example=1),
     *                 @OA\Property(property="question_text", type="string", example="What is the capital of France?"),
     *                 @OA\Property(property="question_type", type="string", example="mcq"),
     *                 @OA\Property(property="points", type="integer", example=5),
     *                 @OA\Property(property="time_limit", type="integer", nullable=true, example=30),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Question not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function getQuestionById(Request $request, $id)
    {
        // GET QUESTION BY ID
        $question = Question::findOrFail($id);

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Question retrieved successfully',
            'question' => $question
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/questions/{ids}",
     *     operationId="deleteQuestions",
     *     tags={"question_management.question"},
     *     summary="Delete one or more questions by ID",
     *     description="Deletes one or multiple questions specified by a comma-separated list of IDs.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Comma-separated list of question IDs to delete",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Questions deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Questions deleted successfully"),
     *             @OA\Property(property="deleted_count", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Question(s) not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Question(s) not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid ID format."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="ids", type="array",
     *                     @OA\Items(type="string", example="Each ID must be an integer.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */

    public function deleteQuestion(Request $request, $ids)
    {

        $idsArray = array_map('intval', explode(',', $ids));

        // VALIDATE IDS
        $existingIds = Question::whereIn('id', $idsArray)->pluck('id')->toArray();

        if (count($existingIds) !== count($idsArray)) {
            return response()->json([
                'success' => false,
                'message' => 'Some or all of the provided IDs do not exist'
            ], 404);
        }

        // DELETE QUESTION
        Question::destroy($idsArray);

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully'
        ], 200);
    }
}
