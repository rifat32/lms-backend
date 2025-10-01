<?php

namespace App\Http\Controllers;

use App\Http\Requests\OptionRequest;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/v1.0/options",
     *     operationId="createOption",
     *     tags={"question_management.options"},
     *     summary="Create a new option",
     *     description="Creates a new option for a specific question.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"text", "is_correct", "question_id"},
     *             @OA\Property(
     *                 property="text",
     *                 type="string",
     *                 example="Option A",
     *                 description="The text value of the option"
     *             ),
     *             @OA\Property(
     *                 property="is_correct",
     *                 type="boolean",
     *                 example=true,
     *                 description="Whether this option is correct"
     *             ),
     *             @OA\Property(
     *                 property="question_id",
     *                 type="integer",
     *                 example=5,
     *                 description="The question ID this option belongs to"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Option created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="text", type="string", example="Option A"),
     *             @OA\Property(property="is_correct", type="boolean", example=true),
     *             @OA\Property(property="question_id", type="integer", example=5),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="text", type="array",
     *                     @OA\Items(type="string", example="The text field is required.")
     *                 ),
     *                 @OA\Property(property="question_id", type="array",
     *                     @OA\Items(type="string", example="The question_id must be a valid integer.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */

    public function create(OptionRequest $request)
    {
        try {
            DB::beginTransaction();
            // Validate the request
            $request_payload = $request->validated();

            // Ensure the question_id is present in the request payload
            $option = Option::create($request_payload);

            //commit the transaction
            DB::commit();

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'Option created successfully',
                'option' => $option
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Put(
     *     path="/v1.0/options",
     *     operationId="updateOption",
     *     tags={"question_management.options"},
     *     summary="Update an existing option",
     *     description="Updates an option's details such as text, correctness, or associated question.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id", "text", "is_correct", "question_id"},
     *             @OA\Property(property="id", type="integer", example=1, description="ID of the option to update"),
     *             @OA\Property(property="text", type="string", example="Option A", description="Option text"),
     *             @OA\Property(property="is_correct", type="boolean", example=true, description="Whether this option is correct"),
     *             @OA\Property(property="question_id", type="integer", example=5, description="Associated question ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="text", type="string", example="Option A"),
     *             @OA\Property(property="is_correct", type="boolean", example=true),
     *             @OA\Property(property="question_id", type="integer", example=5),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid request.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Option not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Option with given ID not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="text", type="array",
     *                     @OA\Items(type="string", example="The text field is required.")
     *                 ),
     *                 @OA\Property(property="question_id", type="array",
     *                     @OA\Items(type="string", example="The question_id must be a valid integer.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function update(OptionRequest $request,)
    {
        try {
            // Begin transaction
            DB::beginTransaction();

            // Validate the request
            $request_payload = $request->validated();

            // Find the option by ID and update it
            $option = Option::findOrFail($request_payload['id']);
            // Ensure the ID is present in the request payload
            $option->update($request_payload);

            // Commit the transaction
            DB::commit();
            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'Option updated successfully',
                'option' => $option
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Get(
     *     path="/v1.0/options",
     *     operationId="getAllOptions",
     *     tags={"question_management.options"},
     *     summary="Get all options",
     *     description="Returns a list of all available options.",
     *     @OA\Response(
     *         response=200,
     *         description="List of options",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="text", type="string", example="Option A"),
     *                 @OA\Property(property="is_correct", type="boolean", example=true),
     *                 @OA\Property(property="question_id", type="integer", example=10),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *             )
     *         )
     *     )
     * )
     */


    public function getAllOptions(Request $request)
    {

        // GET ALL options
          $query = Option::query();

        $options = retrieve_data($query, 'created_at', 'options');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Options retrieved successfully',
            'meta' => $options['meta'],
            'data' => $options['data'],
        ], 200);


    }


    /**
     * @OA\Get(
     *     path="/v1.0/options/{id}",
     *     operationId="getOptionById",
     *     tags={"question_management.options"},
     *     summary="Get option by ID",
     *     description="Retrieve a specific option by its unique ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the option to retrieve",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="text", type="string", example="Option A"),
     *             @OA\Property(property="is_correct", type="boolean", example=true),
     *             @OA\Property(property="question_id", type="integer", example=5),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Option not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Option not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function getOptionById(Request $request, $id)
    {
        // Find the option by ID
        $option = Option::findOrFail($id);

        // Return a success response with the option
        return response()->json([
            'success' => true,
            'message' => 'Option retrieved successfully',
            'option' => $option
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/options/{ids}",
     *     operationId="deleteOptions",
     *     tags={"question_management.options"},
     *     summary="Delete one or more options by ID",
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Comma-separated list of option IDs",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Options deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or missing IDs"
     *     )
     * )
     */

    public function deleteOptions(Request $request, $ids)
    {
        try {
            DB::beginTransaction();
            // Find the option by ID
            $idsArray = array_map('intval', explode(',', $ids));

            if (empty($idsArray)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No IDs provided for deletion'
                ], 400);
            }

            $existingIds = Option::whereIn('id', $idsArray)->pluck('id')->toArray();

            if (count($existingIds) !== count($idsArray)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid IDs provided for deletion'
                ], 400);
            }


            // Delete the option
            Option::destroy($existingIds);

            // Commit the transaction
            DB::commit();

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'Option deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    /**
     * @OA\Get(
     *     path="/v1.0/options/question/{question_id}",
     *     operationId="getOptionByQuestionId",
     *     tags={"question_management.options"},
     *     summary="Get options by question ID",
     *     description="Retrieve all options associated with a specific question ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="question_id",
     *         in="path",
     *         required=true,
     *         description="The ID of the question to retrieve options for",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Options retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="text", type="string", example="Option A"),
     *                 @OA\Property(property="is_correct", type="boolean", example=true),
     *                 @OA\Property(property="question_id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-28T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-28T13:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No options found for the given question ID",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No options found for this question ID.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */


    public function getOptionByQuestionId(Request $request, $question_id)
    {
        // Validate the question_id
        if (!is_numeric($question_id) || $question_id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid question ID provided'
            ], 400);
        }
        // Check if the question exists
        $questionExists = Question::where('id', $question_id)->exists();
        if (!$questionExists) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found'
            ], 404);
        }
        // Retrieve all options for the specified question
        $options = Option::where('question_id', $question_id)->get();

        // Return a success response with the options
        return response()->json([
            'success' => true,
            'message' => 'Options retrieved successfully',
            'options' => $options
        ], 200);
    }
}
