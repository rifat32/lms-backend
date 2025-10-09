<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use App\Models\Sectionable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Lessons",
 *     description="Endpoints for managing lessons (Admin only)"
 * )
 */
class LessonController extends Controller
{


    /**
     * @OA\Get(
     *     path="/v1.0/lessons",
     *     tags={"Lessons"},
     *     operationId="getLessons",
     *     summary="Get all lessons (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *     @OA\Parameter(
     *         name="search_key",
     *         in="query",
     *         required=false,
     *         description="Search by keyword in title or description",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="List of courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Courses retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-19T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid query parameters")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource.")
     *         )
     *     )
     * )
     */

    public function getLessons(Request $request)
    {
if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $query = Lesson::filters();

        $lessons = retrieve_data($query, 'created_at', 'lessons');

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Lesson retrieved successfully',
            'meta' => $lessons['meta'],
            'data' => $lessons['data'],
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/v1.0/lessons",
     *     tags={"Lessons"},
     *     summary="Create a new lesson for a course (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content_type"},
     *             @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *             @OA\Property(property="content_type", type="string", enum={"video","text","file","quiz"}, example="video"),
     *             @OA\Property(property="content_url", type="string", example="https://example.com/video.mp4"),
     *             @OA\Property(property="sort_order", type="integer", example=1),
     *             @OA\Property(property="duration", type="integer", example=45, description="Duration in minutes"),
     *             @OA\Property(property="is_preview", type="boolean", example=true),
     *             @OA\Property(property="is_time_locked", type="boolean", example=false),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-10-01"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *             @OA\Property(property="unlock_day_after_purchase", type="integer", example=7),
     *             @OA\Property(property="description", type="string", example="This lesson introduces Laravel basics."),
     *             @OA\Property(property="content", type="string", example="Lesson detailed text content here..."),
     *             @OA\Property(
     *                 property="section_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={}
     *             ),
     *             @OA\Property(
     *                 property="files",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                 example={}
     *             ),
     *   @OA\Property(
     *                 property="materials",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                  example={}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lesson created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="section_ids", type="string", example="1,2,3"),
     *                 @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *                 @OA\Property(property="duration", type="integer", example=45),
     *                 @OA\Property(property="is_preview", type="boolean", example=true),
     *                 @OA\Property(property="is_time_locked", type="boolean", example=false),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-10-01"),
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                 @OA\Property(property="unlock_day_after_purchase", type="integer", example=7),
     *                 @OA\Property(property="description", type="string", example="This lesson introduces Laravel basics."),
     *                 @OA\Property(property="content", type="string", example="Lesson detailed text content here..."),
     *                 @OA\Property(property="files", type="array", @OA\Items(type="string", example="lessons/files/video.mp4")),
     *                 @OA\Property(property="materials", type="array", @OA\Items(type="string", example="lessons/files/video.mp4")),
     * 
     *                 @OA\Property(property="content_type", type="string", example="video"),
     *                 @OA\Property(property="content_url", type="string", example="https://example.com/video.mp4"),
     *                 @OA\Property(property="sort_order", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */



    public function createLesson(LessonRequest $request)
    {


        try {
if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            DB::beginTransaction();

            $request_payload = $request->validated();


            $lesson = Lesson::create($request_payload); // create first to get ID


          // Helper function to handle upload logic
function processFiles($input_array, $lesson_id, $folder_prefix = 'business_1') {
    $final = [];

    if (!is_array($input_array)) {
        return $final;
    }

    foreach ($input_array as $item) {
        // Case 1: It's a file upload
        if ($item instanceof \Illuminate\Http\UploadedFile) {
            $extension = $item->getClientOriginalExtension();
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $folder_path = "{$folder_prefix}/lesson_{$lesson_id}";
            $item->storeAs($folder_path, $filename, 'public');
            $final[] = $filename;
        }
        // Case 2: It's a string (existing file link)
        elseif (is_string($item)) {
            // Extract the last part after "/"
            $filename = basename($item);
            $final[] = $filename;
        }
    }

    return $final;
}

// Process 'files'
if ($request->has('files')) {
    $processed_files = processFiles($request->input('files'), $lesson->id);
    $lesson->files = $processed_files;
    $lesson->save();
}

// Process 'materials'
if ($request->has('materials')) {
    $processed_materials = processFiles($request->input('materials'), $lesson->id);
    $lesson->materials = $processed_materials;
    $lesson->save();
}





            // Sync sections
            foreach ($request_payload['section_ids'] as $section_id) {
                Sectionable::create([
                    'section_id' => $section_id,
                    'sectionable_id' => $lesson->id,
                    'sectionable_type' => Lesson::class,
                ]);
            }









            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => $lesson
            ], 201);
        } catch (\Exception $e) {
            // Rollback on any failure
            DB::rollBack();

            // 🚨 CRITICAL FIX: Explicitly log the exception details for full context 
            $exception_details = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTraceAsString(), // This is what you need!
            ];





            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Put(
     *     path="/v1.0/lessons",
     *     tags={"Lessons"},
     *     summary="Update an existing lesson (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id",  "title", "content_type"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Updated Lesson Title"),
     *             @OA\Property(property="content_type", type="string", enum={"video","text","file","quiz"}, example="video"),
     *             @OA\Property(property="content_url", type="string", example="https://example.com/updated-video.mp4"),
     *             @OA\Property(property="sort_order", type="integer", example=2),
     *             @OA\Property(property="duration", type="integer", example=50),
     *             @OA\Property(property="is_preview", type="boolean", example=true),
     *             @OA\Property(property="is_time_locked", type="boolean", example=false),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-10-01"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:30"),
     *             @OA\Property(property="unlock_day_after_purchase", type="integer", example=10),
     *             @OA\Property(property="description", type="string", example="Updated lesson description."),
     *             @OA\Property(property="content", type="string", example="Updated detailed content..."),
     *             @OA\Property(
     *                 property="section_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 example={}
     *             ),
     *             @OA\Property(
     *                 property="files",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                  example={}
     *             ),
     *            @OA\Property(
     *                 property="materials",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                 example={}
     *             )
     * 
     * 
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                @OA\Property(property="section_ids", type="string", example="1,2,3"),

     *                 @OA\Property(property="title", type="string", example="Updated Lesson Title"),
     *                 @OA\Property(property="duration", type="integer", example=50),
     *                 @OA\Property(property="is_preview", type="boolean", example=true),
     *                 @OA\Property(property="is_time_locked", type="boolean", example=false),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-10-01"),
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:30"),
     *                 @OA\Property(property="unlock_day_after_purchase", type="integer", example=10),
     *                 @OA\Property(property="description", type="string", example="Updated lesson description."),
     *                 @OA\Property(property="content", type="string", example="Updated detailed content..."),
     *                 @OA\Property(property="files", type="array", @OA\Items(type="string", example="lessons/files/new-video.mp4")),
     *                 @OA\Property(property="materials", type="array", @OA\Items(type="string", example="lessons/files/new-video.mp4")),
     * 
     * 
     *                 @OA\Property(property="content_type", type="string", example="video"),
     *                 @OA\Property(property="content_url", type="string", example="https://example.com/updated-video.mp4"),
     *                 @OA\Property(property="sort_order", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */


    public function updateLesson(LessonRequest $request)
    {
        try {
            if (!auth()->user()->hasAnyRole([ 'owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            DB::beginTransaction();

            $request_payload = $request->validated();

            $lesson = Lesson::find($request_payload['id']);

            if (empty($lesson)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found',
                ], 404);
            }


            if ($request->hasFile('files')) {
                // Get existing files
                $raw_files = $lesson->getRawOriginal('files');
                $existing_files = $raw_files ? json_decode($raw_files, true) : [];

                $uploaded_files = $existing_files; // start with existing

                // Upload new files
                foreach ($request->file('files') as $file) {
                    $path = $file->store('lessons/files', 'public');
                    $uploaded_files[] = $path; // append
                }

                $request_payload['files'] = json_encode($uploaded_files);
            }

            if ($request->hasFile('materials')) {
                // Get existing files
                $raw_files = $lesson->getRawOriginal('files');
                $existing_files = $raw_files ? json_decode($raw_files, true) : [];

                $uploaded_files = $existing_files; // start with existing

                // Upload new files
                foreach ($request->file('materials') as $file) {
                    $path = $file->store('lessons/files', 'public');
                    $uploaded_files[] = $path; // append
                }

                $request_payload['materials'] = json_encode($uploaded_files);
            }


            $lesson->update($request_payload);


            // Sectionable::where('sectionable_id', $lesson->id)
            //     ->where('sectionable_type', Lesson::class)
            //     ->delete();
            // foreach ($request_payload['section_ids'] as $section_id) {
            //     Sectionable::create([
            //         'section_id' => $section_id,
            //         'sectionable_id' => $lesson->id,
            //         'sectionable_type' => Lesson::class,
            //     ]);
            // }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully',
                'data' => $lesson
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    /**
     * @OA\Delete(
     *     path="/v1.0/lessons/{ids}",
     *     tags={"Lessons"},
     *     summary="Delete a lesson (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Lesson ID (comma-separated for multiple like 1,2,3)",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lesson not found")
     *         )
     *     )
     * )
     */
    public function deleteLesson($ids)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

            DB::beginTransaction();

            // Convert comma-separated IDs to array
            $idsArray = array_map('intval', explode(',', $ids));

            // Fetch lessons
            $lessons = Lesson::whereIn('id', $idsArray)->get();

            $existingIds = $lessons->pluck('id')->toArray();

            if (count($existingIds) !== count($idsArray)) {
                $missingIds = array_diff($idsArray, $existingIds);

                return response()->json([
                    'success' => false,
                    'message' => 'Lesson(s) not found',
                    'data' => [
                        'missing_ids' => array_values($missingIds)
                    ]
                ], 404);
            }

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

                $raw_materials = $lesson->getRawOriginal('materials'); // raw JSON string from DB
                $materials = $raw_materials ? json_decode($raw_materials, true) : [];

                if (is_array($materials)) {
                    foreach ($materials as $material) {
                        $path = "business_1/lesson_{$lesson->id}/$material";
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                }
            }




            // Delete lessons
            Lesson::whereIn('id', $existingIds)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
