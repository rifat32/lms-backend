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
     *     tags={"question_management.question_category"},
     *     summary="Create a new question category (role: Admin only)",
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
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

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
     *     summary="Update an existing question category (role: Admin only)",
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
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

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
 *     path="/v1.0/question-categories/validate-slug",
 *     operationId="validateQuestionCategorySlug",
 *     tags={"question_management.question_category"},
 *     summary="Validate a question category slug",
 *     description="Check if a slug is valid and unique for question categories (role: Admin/Owner/Lecturer)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="slug",
 *         in="query",
 *         required=true,
 *         description="Slug to validate",
 *         @OA\Schema(type="string", example="programming")
 *     ),
 *     @OA\Parameter(
 *         name="id",
 *         in="query",
 *         required=false,
 *         description="Question category ID (for updating existing category, exclude self from uniqueness check)",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Validation result",
 *         @OA\JsonContent(
 *             @OA\Property(property="valid", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="The slug is valid.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The slug field is required.")
 *         )
 *     )
 * )
 */
public function validateSlug(Request $request)
{
    $request->validate([
        'slug' => 'required|string|max:255',
        'id'   => 'nullable|integer', // for edit case
    ]);

    $slug = $request->slug;
    $id = $request->id;

    $exists = QuestionCategory::where('slug', $slug)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

    if ($exists) {
        return response()->json([
            'valid' => false,
            'message' => 'The slug is already taken.'
        ], 200);
    }

    return response()->json([
        'valid' => true,
        'message' => 'The slug is valid.'
    ], 200);
}


    /**
     * @OA\Get(
     *     path="/v1.0/question-categories",
     *     operationId="getQuestionCategories",
     *     tags={"question_management.question_category"},
     *     summary="Get all question categories (role: Admin only)",
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
if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

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
     * @OA\Get(
     *     path="/v1.0/question-categories/{id}",
     *     operationId="getQuestionCategoryById",
     *     tags={"question_management.question_category"},
     *     summary="Get a single question category by ID (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course Question ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question Category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Web Development"),
     *             @OA\Property(property="slug", type="string", example="web-development"),
     *             @OA\Property(property="parent_question_category_id", type="integer", example=1),
     *             @OA\Property(property="description", type="string", example="description"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden: Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to view this course category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course Category not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict: Resource conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Conflict occurred while retrieving this resource")
     *         )
     *     )
     * )
     */


    public function getQuestionCategoryById($id)
    {
        if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        // GET QUESTION
        $question = QuestionCategory::findOrFail($id);

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Question category retrieved successfully',
            'data' => $question
        ]);
    }



    /**
     * @OA\Delete(
     *     path="/v1.0/question-categories/{id}",
     *     operationId="deleteQuestionCategory",
     *     tags={"question_management.question_category"},
     *     summary="Delete a question category (role: Admin only)",
     *     description="Deletes a question category by its ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="ID of the question category to delete (comma-separated like 1,2,3)",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question Category deleted successfully",
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

    public function deleteQuestionCategory($ids)
    {
        try {
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            DB::beginTransaction();

            $idsArray = array_map('intval', explode(',', $ids));

            $existingIds = QuestionCategory::whereIn('id', $idsArray)->pluck('id')->toArray();
            if (count($existingIds) !== count($idsArray)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question category not found',
                    'data' => 'Some or all of the provided IDs do not exist'
                ], 404);
            }

            QuestionCategory::destroy($idsArray);

            DB::commit();

            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Question category deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
