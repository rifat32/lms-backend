<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use App\Models\Sectionable;
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
     * @OA\Get(
     *     path="/v1.0/lessons/{id}",
     *     operationId="getLessonById",
     *     tags={"Lessons"},
     *     summary="Get a single lesson by ID (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lesson ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="lesson retrieved successfully",
     *         @OA\JsonContent(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-19T12:00:00Z")
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


    public function getLessonById($id)
    {
        // CHECK IF USER HAS PERMISSION
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        // GET LESSON
        $lesson = Lesson::with(['sections' => function ($query) {
            $query->select('sections.id');
        }])->findOrFail($id);


        // SEND ONLY SECTION IDS
        $lesson->setRelation('sections', $lesson->sections->pluck('id'));

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Lesson retrieved successfully',
            'data' => $lesson
        ]);
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
     *             @OA\Property(property="content_type", type="string", enum={"video","text","file","pdf", "audio"}, example="video"),
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
     *             @OA\Property(property="pdf_read_completion_required", type="boolean", example=""),
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

            // ========================
            // CREATE LESSON
            // ========================
            $lesson = Lesson::create($request_payload); // create first to get ID

            // ========================
            // HANDLE FILES
            // ========================
            $new_files = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $new_files[] = $file->hashName(); // store only the filename
                    $file->storeAs("business_1/lesson_{$lesson->id}", $file->hashName(), 'public');
                }
            }

            if ($request->filled('files') && is_array($request->input('files'))) {
                foreach ($request->input('files') as $f) {
                    if (is_string($f) && $f !== '') {
                        $new_files[] = basename($f);
                    }
                }
            }

            $lesson->files = $new_files;

            // ========================
            // HANDLE MATERIALS
            // ========================
            $new_materials = [];

            if ($request->hasFile('materials')) {
                foreach ($request->file('materials') as $file) {
                    $new_materials[] = $file->hashName(); // store only the filename
                    $file->storeAs("business_1/lesson_{$lesson->id}", $file->hashName(), 'public');
                }
            }

            if ($request->filled('materials') && is_array($request->input('materials'))) {
                foreach ($request->input('materials') as $m) {
                    if (is_string($m) && $m !== '') {
                        $new_materials[] = basename($m);
                    }
                }
            }

            $lesson->materials = $new_materials;

            // ====================
            // HANDLE VIDEO URL
            // =====================
            $preview_video_url = null;
            if (
                isset($request_payload['preview_video_source_type'])
                && $request_payload['preview_video_source_type'] == Lesson::PREVIEW_VIDEO_SOURCE_TYPE['HTML']
            ) {
                // IF UPLOADABLE FILE
                if ($request->hasFile('preview_video_url')) {
                    $file = $request->file('preview_video_url');
                    $preview_video_url_filename = $file->getClientOriginalName();
                    $file->storeAs("business_1/lesson_{$lesson->id}", $preview_video_url_filename, 'public');
                    $preview_video_url = $preview_video_url_filename;
                }

                // IF EXISTING FILE URL
                if ($request->filled('preview_video_url') && is_string($request->input('preview_video_url'))) {
                    $preview_video_url = basename($request->input('preview_video_url'));
                }
            } else {
                $preview_video_url = $request_payload['preview_video_url'] ?? null;
            }

            $lesson->preview_video_url = $preview_video_url;

            // ========================================
            // HANDLE VIDEO POSTER
            // ========================================
            $preview_video_poster = null;
            if ($request->hasFile('preview_video_poster')) {
                $file = $request->file('preview_video_poster');
                $preview_video_poster_filename = $file->getClientOriginalName();
                $file->storeAs("business_1/lesson_{$lesson->id}", $preview_video_poster_filename, 'public');
                $preview_video_poster = $preview_video_poster_filename;
            } else if ($request->filled('preview_video_poster') && is_string($request->input('preview_video_poster'))) {
                $preview_video_poster = basename($request->input('preview_video_poster'));
            }

            $lesson->preview_video_poster = $preview_video_poster;

            // =========================================
            // HANDLE SUBTITLE
            // =========================================
            if ($request->hasFile('subtitle')) {
                $file = $request->file('subtitle');
                $subtitle_filename = $file->getClientOriginalName();
                $file->storeAs("business_1/lesson_{$lesson->id}", $subtitle_filename, 'public');
                $lesson->subtitle = $subtitle_filename;
            } else if ($request->filled('subtitle') && is_string($request->input('subtitle'))) {
                $lesson->subtitle = basename($request->input('subtitle'));
            }


            $lesson->save();

            // Load only section IDs directly (no need to load full models)
            $sectionIds = $lesson->sections()->pluck('id');

            // Replace the relation with just IDs
            $lesson->setRelation('sections', $sectionIds);
            // ========================
            // SYNC SECTIONS
            // ========================
            if (!empty($request_payload['section_ids']) && is_array($request_payload['section_ids'])) {
                foreach ($request_payload['section_ids'] as $section_id) {
                    Sectionable::create([
                        'section_id' => $section_id,
                        'sectionable_id' => $lesson->id,
                        'sectionable_type' => Lesson::class,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => $lesson
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'data' => [
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]
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
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
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

            // ========================
            // HANDLE FILES
            // ========================
            $new_files = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $new_files[] = $file->hashName(); // store only the filename
                    $file->storeAs("business_1/lesson_{$lesson->id}", $file->hashName(), 'public');
                }
            }

            if ($request->filled('files') && is_array($request->input('files'))) {
                foreach ($request->input('files') as $f) {
                    if (is_string($f) && $f !== '') {
                        // take last part after /
                        $new_files[] = basename($f);
                    }
                }
            }

            $request_payload['files'] = $new_files;

            // ========================
            // HANDLE MATERIALS
            // ========================
            $new_materials = [];

            if ($request->hasFile('materials')) {
                foreach ($request->file('materials') as $file) {
                    $new_materials[] = $file->hashName(); // store only the filename
                    $file->storeAs("business_1/lesson_{$lesson->id}", $file->hashName(), 'public');
                }
            }

            if ($request->filled('materials') && is_array($request->input('materials'))) {
                foreach ($request->input('materials') as $m) {
                    if (is_string($m) && $m !== '') {
                        $new_materials[] = basename($m); // store only filename
                    }
                }
            }

            $request_payload['materials'] = $new_materials;

            // ====================
            // HANDLE VIDEO URL
            // =====================
            $preview_video_url = null;
            if (
                isset($request_payload['preview_video_source_type']) &&
                $request_payload['preview_video_source_type'] == Lesson::PREVIEW_VIDEO_SOURCE_TYPE['HTML']
            ) {
                // IF UPLOADABLE FILE
                if ($request->hasFile('preview_video_url')) {
                    $file = $request->file('preview_video_url');
                    $preview_video_url_filename = $file->getClientOriginalName();
                    $file->storeAs("business_1/lesson_{$lesson->id}", $preview_video_url_filename, 'public');
                    $preview_video_url = $preview_video_url_filename;
                }

                // IF EXISTING FILE URL
                if ($request->filled('preview_video_url') && is_string($request->input('preview_video_url'))) {
                    $preview_video_url = basename($request->input('preview_video_url'));
                }
            } else {
                $preview_video_url = $request_payload['preview_video_url'] ?? null;
            }

            $request_payload['preview_video_url'] = $preview_video_url;

            // ========================================
            // HANDLE VIDEO POSTER
            // ========================================
            $preview_video_poster = null;
            if ($request->hasFile('preview_video_poster')) {
                $file = $request->file('preview_video_poster');
                $preview_video_poster_filename = $file->getClientOriginalName();
                $file->storeAs("business_1/lesson_{$lesson->id}", $preview_video_poster_filename, 'public');
                $preview_video_poster = $preview_video_poster_filename;
            } else if ($request->filled('preview_video_poster') && is_string($request->input('preview_video_poster'))) {
                $preview_video_poster = basename($request->input('preview_video_poster'));
            }

            $request_payload['preview_video_poster'] = $preview_video_poster;

            // =========================================
            // HANDLE SUBTITLE
            // =========================================
            if ($request->hasFile('subtitle')) {
                $file = $request->file('subtitle');
                $subtitle_filename = $file->getClientOriginalName();
                $file->storeAs("business_1/lesson_{$lesson->id}", $subtitle_filename, 'public');
                $request_payload['subtitle'] = $subtitle_filename;
            } else if ($request->filled('subtitle') && is_string($request->input('subtitle'))) {
                $request_payload['subtitle'] = basename($request->input('subtitle'));
            }

            // ========================
            // UPDATE LESSON
            // ========================
            $lesson->update($request_payload);

            // Load only section IDs directly (no need to load full models)
            $sectionIds = $lesson->sections()->pluck('id');

            // Replace the relation with just IDs
            $lesson->setRelation('sections', $sectionIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully',
                'data' => $lesson
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'data' => [
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]
            ], 500);
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
