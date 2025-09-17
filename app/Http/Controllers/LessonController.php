<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

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
     *     path="courses/{course_id}/lessons",
     *     tags={"Lessons"},
     *     summary="Create a new lesson for a course (Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="course_id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content_type","content_url"},
     *             @OA\Property(property="title", type="string", example="Introduction to Laravel"),
     *             @OA\Property(property="content_type", type="string", example="video"),
     *             @OA\Property(property="content_url", type="string", example="https://example.com/video.mp4"),
     *             @OA\Property(property="sort_order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Lesson created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
    public function store(Request $request, $course_id)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'content_type' => 'required|string',
            'content_url' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['course_id'] = $course_id;

        $lesson = Lesson::create($validated);

        return response()->json($lesson, 201);
    }

    /**
     * @OA\Put(
     *     path="lessons/{id}",
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
     *             @OA\Property(property="title", type="string", example="Updated Lesson Title"),
     *             @OA\Property(property="content_type", type="string", example="video"),
     *             @OA\Property(property="content_url", type="string", example="https://example.com/updated-video.mp4"),
     *             @OA\Property(property="sort_order", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Lesson updated successfully"),
     *     @OA\Response(response=404, description="Lesson not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'content_type' => 'sometimes|string',
            'content_url' => 'sometimes|string',
            'sort_order' => 'sometimes|integer',
        ]);

        $lesson->update($validated);

        return response()->json($lesson);
    }
}
