<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
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
     * @OA\Post(
     *     path="/v1.0/lessons",
     *     tags={"Lessons"},
     *     summary="Create a new lesson for a course (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content_type", "course_id","section_id"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *             @OA\Property(property="content_type", type="string", enum={"video","text","file","quiz"}, example="video"),
     *             @OA\Property(property="content_url", type="string", example="https://example.com/video.mp4"),
     *             @OA\Property(property="sort_order", type="integer", example=1),
     *             @OA\Property(property="section_id", type="integer", example=1),
     *             @OA\Property(property="duration", type="integer", example=45, description="Duration in minutes"),
     *             @OA\Property(property="is_preview", type="boolean", example=true),
     *             @OA\Property(property="is_time_locked", type="boolean", example=false),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-10-01"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *             @OA\Property(property="unlock_day_after_purchase", type="integer", example=7),
     *             @OA\Property(property="description", type="string", example="This lesson introduces Laravel basics."),
     *             @OA\Property(property="content", type="string", example="Lesson detailed text content here..."),
     *             @OA\Property(
     *                 property="files",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary")
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
     *                 @OA\Property(property="course_id", type="integer", example=101),
     *                 @OA\Property(property="section_id", type="integer", example=1),
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
            DB::beginTransaction();

            $request_payload = $request->validated();

            // Handle file uploads
            if ($request->hasFile('files')) {
                $uploaded_files = [];
                foreach ($request->file('files') as $file) {
                    $path = $file->store('lessons/files', 'public');
                    $uploaded_files[] = $path;
                }
                $request_payload['files'] = json_encode($uploaded_files);
            }

            $lesson = Lesson::create($request_payload);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => $lesson
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
    /**
     * @OA\Put(
     *     path="/v1.0/lessons/{id}",
     *     tags={"Lessons"},
     *     summary="Update an existing lesson (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lesson ID",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "course_id", "title", "content_type"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1),
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
     *                 property="files",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary")
     *             )
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
     *                 @OA\Property(property="course_id", type="integer", example=101),
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


    public function updateLesson(LessonRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $request_payload = $request->validated();

            $lesson = Lesson::find($id);

            if (empty($lesson)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found',
                ], 404);
            }

            // Handle file uploads
            if ($request->hasFile('files')) {
                $uploaded_files = [];
                foreach ($request->file('files') as $file) {
                    $path = $file->store('lessons/files', 'public');
                    $uploaded_files[] = $path;
                }
                $request_payload['files'] = json_encode($uploaded_files);
            }

            $lesson->update($request_payload);

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
     *     summary="Delete a lesson",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Lesson ID (comma-separated for multiple)",
     *         @OA\Schema(type="string", example="1,2,3")
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
            DB::beginTransaction();

            // Validate and convert comma-separated IDs to an array
            $idsArray = array_map('intval', explode(',', $ids));

            // Get existing lessons
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

            // Delete lesson files
            foreach ($lessons as $lesson) {
                if (!empty($lesson->files)) {
                    $files = json_decode($lesson->files, true);
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            Storage::disk('public')->delete($file);
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
