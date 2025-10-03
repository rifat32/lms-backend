<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Http\Requests\SectionWithLessonRequest;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SectionController extends Controller
{

    /**
     * @OA\Delete(
     *     path="/v1.0/sections/{ids}",
     *     operationId="deleteSection",
     *     tags={"section"},
     *     summary="Delete a section and its lessons",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ID of the section to delete (comma-separated for multiple)",
     *         required=true,
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Section deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Section deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Section not found")
     *         )
     *     )
     * )
     */
    public function deleteSection($ids)
    {
        try {
            DB::beginTransaction();

            $idsArray = array_map('intval', explode(',', $ids));

            // Find existing section IDs
            $existingIds = Section::whereIn('id', $idsArray)->pluck('id')->toArray();

            if (count($existingIds) !== count($idsArray)) {
                $missingIds = array_diff($idsArray, $existingIds);
                return response()->json([
                    'success' => false,
                    'message' => 'Some of the data not found',
                    'data' => [
                        'missing_ids' => $missingIds
                    ]
                ], 400);
            }

            // Delete all lesson files
            $lessons = Lesson::whereIn('section_id', $existingIds)->get();

             // Delete lesson files (use raw DB value, not accessor!)
        foreach ($lessons as $lesson) {
            $raw_files = $lesson->getRawOriginal('files'); // raw JSON string from DB
            $files = $raw_files ? json_decode($raw_files, true) : [];

            if (is_array($files)) {
                foreach ($files as $file) {
                    $path = "business_1/lesson_{$lesson->id}/$file";
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        }

            // Delete sections (cascade deletes lessons if DB is set up)
            Section::whereIn('id', $existingIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sections deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    /**
     * @OA\Post(
     *     path="/v1.0/sections",
     *     operationId="createSection",
     *     tags={"section"},
     *     summary="Create a new section for a course (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "course_id"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Introduction to Laravel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Section created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Section created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T12:00:00Z")
     *             )
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
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A section with this title already exists for this course.")
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
     *                 @OA\Property(property="course_id", type="array",
     *                     @OA\Items(type="string", example="The course_id field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function createSection(SectionRequest $request)
    {
        try {
            // Begin transaction
            DB::beginTransaction();

            // Validate the request
            $request_payload = $request->validated();

            $section = Section::create($request_payload);

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Section created successfully',
                'data' => $section
            ], 201);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }


    /**
     * @OA\Put(
     *     path="/v1.0/sections",
     *     operationId="updateSection",
     *     tags={"section"},
     *     summary="Create a new section for a course (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "title", "course_id"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Introduction to Laravel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Section updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Section updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T12:00:00Z")
     *             )
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
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A section with this title already exists for this course.")
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
     *                 @OA\Property(property="course_id", type="array",
     *                     @OA\Items(type="string", example="The course_id field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function updateSection(SectionRequest $request)
    {
        try {
            // Begin transaction
            DB::beginTransaction();

            // Validate the request
            $request_payload = $request->validated();

            $section = Section::findOrFail($request_payload['id']);

            $section->update($request_payload);

            // Commit the transaction
            DB::commit();
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully',
                'data' => $section
            ], 200);
        } catch (\Throwable $th) {
            // Rollback the transaction in case of error
            DB::rollBack();
            throw $th;
        }
    }



     /**
 * @OA\Put(
 *     path="/v1.0/sections-with-lessons",
 *     operationId="updateSectionWithLessons",
 *     tags={"section"},
 *     summary="Update a section with lessons and quizzes (Admin only)",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id", "title", "course_id"},
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="course_id", type="integer", example=1),
 *             @OA\Property(property="title", type="string", example="Introduction to Laravel"),
 *             @OA\Property(
 *                 property="sectionable",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"id","type"},
 *                     @OA\Property(property="id", type="integer", example=10),
 *                     @OA\Property(property="type", type="string", enum={"lesson","quiz"}, example="lesson"),
 *                     @OA\Property(property="order", type="integer", example=1)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Section updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Section updated successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="course_id", type="integer", example=1),
 *                 @OA\Property(property="title", type="string", example="Introduction to Laravel"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T12:00:00Z"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T12:00:00Z"),
 *                 @OA\Property(property="lessons", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=10),
 *                         @OA\Property(property="title", type="string", example="Lesson One"),
 *                         @OA\Property(property="pivot", type="object",
 *                             @OA\Property(property="order", type="integer", example=1)
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(property="quizzes", type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=5),
 *                         @OA\Property(property="title", type="string", example="Quiz One"),
 *                         @OA\Property(property="pivot", type="object",
 *                             @OA\Property(property="order", type="integer", example=2)
 *                         )
 *                     )
 *                 )
 *             )
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
 *         description="Course not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Course not found.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=409,
 *         description="Conflict",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="A section with this title already exists for this course.")
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
 *                 @OA\Property(property="course_id", type="array",
 *                     @OA\Items(type="string", example="The course_id field is required.")
 *                 ),
 *                 @OA\Property(property="sectionable", type="array",
 *                     @OA\Items(type="string", example="The sectionable field is required.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */


  public function updateSectionWithLessons(SectionWithLessonRequest $request)
{
    try {
        DB::beginTransaction();

        $request_payload = $request->validated();

        $section = Section::findOrFail($request_payload['id']);

        // update section info
        $section->update($request_payload);

        // delete old sectionables
        $section->sectionables()->delete();

        // attach new sectionables
        foreach ($request_payload['sectionable'] as $item) {
            $modelClass = $item['type'] === 'lesson'
                ? \App\Models\Lesson::class
                : \App\Models\Quiz::class;

            $section->sectionables()->create([
                'sectionable_id' => $item['id'],
                'sectionable_type' => $modelClass,
                'order' => $item['order'] ?? null,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section->load(['lessons', 'quizzes'])
        ], 200);

    } catch (\Throwable $th) {
        DB::rollBack();
        throw $th;
    }
}

    /**
     * @OA\Get(
     *     path="/v1.0/sections",
     *     operationId="getSections",
     *     tags={"section"},
     *     summary="Fetch all sections",
     *     description="Retrieve a list of all sections",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sections fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sections fetched successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="course_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *                     @OA\Property(property="created_by", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T12:00:00Z")
     *                 )
     *             )
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
     *     )
     * )
     */
    public function getSections(Request $request)
    {

         $query = Section::with(['sectionables.sectionable']);

        $sections = retrieve_data($query, 'created_at', 'sections');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Sections retrieved successfully',
            'meta' => $sections['meta'],
            'data' => $sections['data'],
        ], 200);


    }

    /**
     * @OA\Get(
     *     path="/v1.0/sections/{id}",
     *     operationId="getSectionById",
     *     tags={"section"},
     *     summary="Fetch a single section by ID",
     *     description="Retrieve details of a specific section using its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the section",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Section fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Section fetched successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="course_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *                 @OA\Property(property="created_by", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T12:00:00Z")
     *             )
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
     *         description="Section not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Section not found.")
     *         )
     *     )
     * )
     */
    public function getSectionById($id)
    {
        $section = Section::with(['sectionables.sectionable'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Section fetched successfully',
            'data' => $section
        ], 200);
    }




}
