<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Option;
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
     *     summary="Create or update a question (role: Admin only)",
     *     description="Creates a new question or updates existing options for a specific quiz.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"question_text", "question_type", "points", "options"},
     *
     *             @OA\Property(
     *                 property="question_text",
     *                 type="string",
     *                 maxLength=255,
     *                 example="What is the capital of France?",
     *                 description="The text of the question"
     *             ),
     *
     *             @OA\Property(
     *                 property="question_type",
     *                 type="string",
     *                 enum={"true_false", "single", "multiple", "matching", "file_matching", "keywords", "fill_in_the_blanks"},
     *                 example="true_false",
     *                 description="Type of the question"
     *             ),
     *
     *             @OA\Property(property="points", type="integer", minimum=1, example=5, description="Number of points for the question"),
     *             @OA\Property(property="time_limit", type="integer", nullable=true, minimum=0, example=30, description="Time limit in seconds (nullable)"),
     *
     *             @OA\Property(
     *                 property="category_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of category IDs for this question"
     *             ),
     *
     *             @OA\Property(property="is_required", type="boolean", example=true),
     *
     *             @OA\Property(
     *                 property="options",
     *                 type="array",
     *                 description="List of options for the question",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", nullable=true, example=null, description="ID when updating an option"),
     *                     @OA\Property(property="option_text", type="string", nullable=true, example="Paris", description="Option text"),
     *                     @OA\Property(property="is_correct", type="boolean", example=true, description="Whether the option is correct"),
     *                     @OA\Property(property="explanation", type="string", nullable=true, example="Paris is the capital of France", description="Explanation for the option"),
     *                     @OA\Property(property="image", type="string", nullable=true, example="https://example.com/image.png", description="Image URL or file path"),
     *                     @OA\Property(property="matching_pair_text", type="string", nullable=true, example="France", description="Matching pair text"),
     *                     @OA\Property(property="matching_pair_image", type="string", nullable=true, example="https://example.com/pair-image.png", description="Matching pair image URL or file path")
     *                 )
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
     *             @OA\Property(property="message", type="string", example="Question created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question_text", type="string", example="What is the capital of France?"),
     *                 @OA\Property(property="question_type", type="string", example="single"),
     *                 @OA\Property(property="points", type="integer", example=5),
     *                 @OA\Property(property="time_limit", type="integer", example=30, nullable=true),
     *                 @OA\Property(property="is_required", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(
     *                     property="options",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="option_text", type="string", example="Paris"),
     *                         @OA\Property(property="is_correct", type="boolean", example=true),
     *                         @OA\Property(property="explanation", type="string", example="Paris is the capital of France"),
     *                         @OA\Property(property="image", type="string", example="https://example.com/image.png"),
     *                         @OA\Property(property="matching_pair_text", type="string", example="France"),
     *                         @OA\Property(property="matching_pair_image", type="string", example="https://example.com/pair-image.png")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Quiz not found"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */




    public function createQuestion(QuestionRequest $request)
    {
        try {
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            // Begin transaction
            DB::beginTransaction();

            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // CREATE QUESTION
            $question = Question::create($request_payload);

            $question->categories()->sync($request_payload["category_ids"]);


            foreach ($request->options as $optData) {
                // If ID is passed, update existing option, otherwise create new
                $option = Option::updateOrCreate(
                    ['id' => $optData['id'] ?? null],
                    [
                        'question_id' => $question->id,
                        'option_text' => $optData['option_text'] ?? null,
                        'is_correct' => $optData['is_correct'],
                        'explanation' => $optData['explanation'] ?? null,
                    ]
                );

                $base_folder = "business_1/question_{$question->id}";

                // Handle option image
                if (!empty($optData['image'])) {
                    if (is_file($optData['image'])) {
                        $image_file = $optData['image'];
                        $extension = $image_file->getClientOriginalExtension();
                        $filename = uniqid() . '_' . time() . '.' . $extension;
                        $folder_path = "{$base_folder}/options";
                        $image_file->storeAs($folder_path, $filename, 'public');
                        $option->image = $filename; // store only filename
                    } else {
                        $option->image = $optData['image']; // if string URL
                    }
                }

                // Handle matching_pair_image
                if (!empty($optData['matching_pair_image'])) {
                    if (is_file($optData['matching_pair_image'])) {
                        $image_file = $optData['matching_pair_image'];
                        $extension = $image_file->getClientOriginalExtension();
                        $filename = uniqid() . '_' . time() . '.' . $extension;
                        $folder_path = "{$base_folder}/matching";
                        $image_file->storeAs($folder_path, $filename, 'public');
                        $option->matching_pair_image = $filename; // store only filename
                    } else {
                        $option->matching_pair_image = $optData['matching_pair_image']; // if string URL
                    }
                }

                $option->save();
            }


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
     *     path="/v1.0/questions",
     *     operationId="updateQuestion",
     *     tags={"question_management.question"},
     *     summary="Update an existing question with options (role: Admin only)",
     *     description="Updates a question and its options by question ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id","question_text", "question_type", "points", "options"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="question_text", type="string", maxLength=255, example="Updated question text"),
     *             @OA\Property(property="question_type", type="string", example="single", enum={"true_false","single","multiple","matching","file_matching","keywords","fill_in_the_blanks"}),
     *             @OA\Property(
     *                 property="category_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of category IDs for this course"
     *             ),
     *             @OA\Property(property="points", type="integer", example=5, minimum=1),
     *             @OA\Property(property="time_limit", type="integer", nullable=true, example=30, minimum=0),
     *             @OA\Property(property="is_required", type="boolean", example=true),
     *             @OA\Property(
     *                 property="options",
     *                 type="array",
     *                 description="List of options for the question",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="option_text", type="string", nullable=true, example="Paris"),
     *                     @OA\Property(property="is_correct", type="boolean", example=true),
     *                     @OA\Property(property="explanation", type="string", nullable=true, example="Paris is the capital of France"),
     *                     @OA\Property(property="image", type="string", nullable=true, example="https://example.com/image.png"),
     *                     @OA\Property(property="matching_pair_text", type="string", nullable=true, example="France"),
     *                     @OA\Property(property="matching_pair_image", type="string", nullable=true, example="https://example.com/pair-image.png")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Question updated successfully"),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Question not found"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */



  public function updateQuestion(QuestionRequest $request)
{
    try {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        DB::beginTransaction();

        $request_payload = $request->validated();
        $question = Question::findOrFail($request_payload['id']);

        // Check if any quiz containing this question has attempts
        $has_attempts = $question->quizzes()
            ->whereHas('all_quiz_attempts')
            ->exists();

        if ($has_attempts) {
            // ⚠️ Restrict updates if attempts exist
            $question->update([
                'question_text' => $request_payload['question_text']
            ]);

            if (!empty($request->options)) {
                foreach ($request->options as $opt_data) {
                    if (!empty($opt_data['id'])) {
                        $option = Option::find($opt_data['id']);
                        if ($option && $option->question_id === $question->id) {
                            $option->update([
                                'option_text' => $opt_data['option_text'] ?? $option->option_text,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Only titles were updated (since quiz attempts exist)',
                'question' => $question
            ], 200);

        } else {
            // ✅ No attempts — full update allowed
            $question->update($request_payload);
            $question->categories()->sync($request_payload["category_ids"]);

            if (!empty($request->options)) {
                foreach ($request->options as $optData) {
                    $option = Option::updateOrCreate(
                        ['id' => $optData['id'] ?? null],
                        [
                            'question_id' => $question->id,
                            'option_text' => $optData['option_text'] ?? null,
                            'is_correct' => $optData['is_correct'],
                            'explanation' => $optData['explanation'] ?? null,
                            'matching_pair_text' => $optData['matching_pair_text'] ?? null,
                        ]
                    );

                    $base_folder = "business_1/question_{$question->id}";

                    // Handle option image
                    if (!empty($optData['image'])) {
                        if (is_file($optData['image'])) {
                            $image_file = $optData['image'];
                            $extension = $image_file->getClientOriginalExtension();
                            $filename = uniqid() . '_' . time() . '.' . $extension;
                            $folder_path = "{$base_folder}/options";
                            $image_file->storeAs($folder_path, $filename, 'public');
                            $option->image = $filename;
                        } else {
                            $option->image = $optData['image'];
                        }
                    }

                    // Handle matching_pair_image
                    if (!empty($optData['matching_pair_image'])) {
                        if (is_file($optData['matching_pair_image'])) {
                            $image_file = $optData['matching_pair_image'];
                            $extension = $image_file->getClientOriginalExtension();
                            $filename = uniqid() . '_' . time() . '.' . $extension;
                            $folder_path = "{$base_folder}/matching";
                            $image_file->storeAs($folder_path, $filename, 'public');
                            $option->matching_pair_image = $filename;
                        } else {
                            $option->matching_pair_image = $optData['matching_pair_image'];
                        }
                    }

                    $option->save();
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'question' => $question
            ], 200);
        }

    } catch (\Throwable $th) {
        DB::rollBack();
        throw $th;
    }
}



    /**
     * @OA\Get(
     *     path="/v1.0/questions",
     *     operationId="getAllQuestions",
     *     tags={"question_management.question"},
     *     summary="Get all questions (role: Admin only)",
     *     description="Retrieve a list of all questions.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *
     *     @OA\Parameter(
     *         name="question_type",
     *         in="query",
     *         required=false,
     *         description="Filter by question type",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of questions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
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
        if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        // GET ALL QUESTIONS
        $query = Question::with(['options', "categories"])->filters();

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
     *     summary="Get question by ID (role: Admin only)",
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
        if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        // GET QUESTION BY ID
        $question = Question::with('options', "categories")->findOrFail($id);

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
     *     summary="Delete one or more questions by ID (role: Admin only)",
     *     description="Deletes one or multiple questions specified by a comma-separated list of IDs.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Comma-separated list of question IDs to delete like 1,2,3",
     *         @OA\Schema(type="string", example="")
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
    if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
        return response()->json([
            "message" => "You can not perform this action"
        ], 401);
    }

    $ids_array = array_map('intval', explode(',', $ids));

    // VALIDATE IDS EXISTENCE
    $existing_ids = Question::whereIn('id', $ids_array)->pluck('id')->toArray();

    if (count($existing_ids) !== count($ids_array)) {
        return response()->json([
            'success' => false,
            'message' => 'Some or all of the provided IDs do not exist'
        ], 404);
    }

    // 🔍 CHECK FOR QUIZ ATTEMPTS
    $questions_with_attempts = Question::whereIn('id', $ids_array)
        ->whereHas('quizzes.all_quiz_attempts') // means any quiz with attempts
        ->pluck('id')
        ->toArray();

    if (!empty($questions_with_attempts)) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot delete these questions because they are part of quizzes that already have attempts.',
            'question_ids' => $questions_with_attempts
        ], 403);
    }

    // ✅ SAFE TO DELETE
    Question::destroy($ids_array);

    return response()->json([
        'success' => true,
        'message' => 'Question(s) deleted successfully'
    ], 200);
}




}
