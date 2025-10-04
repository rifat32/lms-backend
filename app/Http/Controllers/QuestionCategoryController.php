<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionCategoryRequest;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="question_management.category",
 *     description="Endpoints for managing question categories"
 * )
 */
class QuestionCategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/v1.0/question-categories",
     *     operationId="createQuestionCategory",
     *     tags={"question_management.category"},
     *     summary="Create a new question category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "slug"},
     *             @OA\Property(property="title", type="string", example="Programming"),
     *             @OA\Property(property="description", type="string", example="Questions related to programming topics."),
     *             @OA\Property(property="slug", type="string", example="programming"),
     *             @OA\Property(property="parent_question_category_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Question category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Question category created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Programming"),
     *                 @OA\Property(property="description", type="string", example="Questions related to programming topics."),
     *                 @OA\Property(property="slug", type="string", example="programming"),
     *                 @OA\Property(property="parent_question_category_id", type="integer", example=null),
     *                 @OA\Property(property="created_at", type="string", example="2025-10-04T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createQuestionCategory(QuestionCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $payload = $request->validated();
            $category = QuestionCategory::create($payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question category created successfully',
                'data' => $category
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }



    /**
     * @OA\Put(
     *     path="/v1.0/question-categories",
     *     tags={"question_management.question_category"},
     *     operationId="updateQuestionCategory",
     *     summary="Update an existing question category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","title"},
     *             @OA\Property(property="id", type="integer", example=5),
     *             @OA\Property(property="title", type="string", example="Programming"),
     *             @OA\Property(property="description", type="string", example="All programming related questions"),
     *             @OA\Property(property="slug", type="string", example="programming"),
     *             @OA\Property(property="parent_question_category_id", type="integer", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Question category updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="title", type="string", example="Programming"),
     *                 @OA\Property(property="description", type="string", example="All programming related questions"),
     *                 @OA\Property(property="slug", type="string", example="programming"),
     *                 @OA\Property(property="parent_question_category_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-04T21:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Question category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Question category not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updateQuestionCategory(QuestionCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $payload = $request->validated();

            $category = QuestionCategory::find($payload['id']);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question category not found'
                ], 404);
            }

            $category->update($payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question category updated successfully',
                'data' => $category
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


   /**
 * @OA\Get(
 *     path="/v1.0/question-categories",
 *     operationId="getQuestionCategories",
 *     tags={"question_management.category"},
 *     summary="Get all question categories",
 *     description="Fetches all question categories in the system.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Question categories fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Mathematics"),
 *                     @OA\Property(property="created_at", type="string", example="2025-10-04T15:23:01.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", example="2025-10-04T15:23:01.000000Z")
 *                 )
 *             )
 *         )
 *     )
 * )
 */

    public function getQuestionCategories(Request $request)
    {

        $query = QuestionCategory::query();

        $categories = retrieve_data($query, 'created_at', 'question_categories');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Question Category retrieved successfully',
            'meta' => $categories['meta'],
            'data' => $categories['data'],
        ], 200);



    }

   /**
 * @OA\Delete(
 *     path="/v1.0/question-categories/{id}",
 *     operationId="deleteQuestionCategory",
 *     tags={"question_management.category"},
 *     summary="Delete a question category",
 *     description="Deletes a question category by its ID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the question category to delete",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Category deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Question category deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Category not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Question category not found")
 *         )
 *     )
 * )
 */

    public function deleteQuestionCategory($id)
    {
        try {
            DB::beginTransaction();

            $category = QuestionCategory::find($id);
            if (!$category) {
                return response()->json(['success' => false, 'message' => 'Question category not found'], 404);
            }

            $category->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Question category deleted successfully'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
