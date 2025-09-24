<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/v1.0/questions",
     *     operationId="createQuestion",
     *     tags={"Questions"},
     *     summary="Create a new question",
     *     security={{"bearerAuth":{}}},
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
     *                 minimum=0,
     *                 nullable=true,
     *                 example=30,
     *                 description="Time limit for the question in seconds (nullable)"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Question created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Question created successfully"),
     *             @OA\Property(property="question", ref="#/components/schemas/Question")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A lesson with this title already exists for this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(property="content_type", type="array",
     *                     @OA\Items(type="string", example="The selected content type is invalid.")
     *                 ),
     *                 @OA\Property(property="content_url", type="array",
     *                     @OA\Items(type="string", example="The content URL must be a valid URL.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */


    public function createQuestion(QuestionRequest $request)
    {
        // VALIDATE PAYLOAD
        $request_payload = $request->validated();

        // CREATE QUESTION
        $question = Question::create($request_payload);

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'question' => $question
        ], 201);
    }


    /**
     * @OA\Put(
     *     path="/v1.0/questions",
     *     operationId="updateQuestion",
     *     tags={"Questions"},
     *     summary="Update an existing question",
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
     *                 minimum=0,
     *                 nullable=true,
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
     *             @OA\Property(property="question", ref="#/components/schemas/Question")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A lesson with this title already exists for this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(property="content_type", type="array",
     *                     @OA\Items(type="string", example="The selected content type is invalid.")
     *                 ),
     *                 @OA\Property(property="content_url", type="array",
     *                     @OA\Items(type="string", example="The content URL must be a valid URL.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function updateQuestion(QuestionRequest $request)
    {
        // VALIDATE PAYLOAD
        $request_payload = $request->validated();

        // UPDATE QUESTION
        $question = Question::findOrFail($request_payload['id']);
        $question->update($request_payload);

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'question' => $question
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/questions",
     *     operationId="getAllQuestions",
     *     tags={"Questions"},
     *     summary="Get all questions",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of questions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Question")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A lesson with this title already exists for this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(property="content_type", type="array",
     *                     @OA\Items(type="string", example="The selected content type is invalid.")
     *                 ),
     *                 @OA\Property(property="content_url", type="array",
     *                     @OA\Items(type="string", example="The content URL must be a valid URL.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllQuestions(Request $request)
    {

        // GET ALL QUESTIONS
        $query = Question::query();
        $questions = $query->all();



        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Questions retrieved successfully',
            'questions' => $questions
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/questions/{id}",
     *     operationId="getQuestionById",
     *     tags={"Questions"},
     *     summary="Get question by ID",
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
     *             @OA\Property(property="question", ref="#/components/schemas/Question")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A lesson with this title already exists for this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(property="content_type", type="array",
     *                     @OA\Items(type="string", example="The selected content type is invalid.")
     *                 ),
     *                 @OA\Property(property="content_url", type="array",
     *                     @OA\Items(type="string", example="The content URL must be a valid URL.")
     *                 )
     *             )
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
     *     tags={"Questions"},
     *     summary="Delete one or more questions by ID",
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
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A lesson with this title already exists for this course.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 ),
     *                 @OA\Property(property="content_type", type="array",
     *                     @OA\Items(type="string", example="The selected content type is invalid.")
     *                 ),
     *                 @OA\Property(property="content_url", type="array",
     *                     @OA\Items(type="string", example="The content URL must be a valid URL.")
     *                 )
     *             )
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
