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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/OptionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Option created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Option")
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/OptionRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Option")
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
     *     @OA\Response(
     *         response=200,
     *         description="List of options",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Option")
     *         )
     *     )
     * )
     */

    public function getALlOptions(Request $request)
    {
        // Retrieve all options for the specified question
        $query = Option::query();

        $options = $query->get();

        // Return a success response with the options
        return response()->json([
            'success' => true,
            'message' => 'Options retrieved successfully',
            'options' => $options
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/v1.0/options/{id}",
     *     operationId="getOptionById",
     *     tags={"question_management.options"},
     *     summary="Get option by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Option retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Option")
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
     *     @OA\Parameter(
     *         name="question_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Options retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Option")
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
