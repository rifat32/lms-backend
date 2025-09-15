<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    // POST /api/courses/{course_id}/lessons (Admin)
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

    // PUT /api/lessons/{id} (Admin)
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