<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // GET /api/courses
    public function index(Request $request)
    {
        $query = Course::query();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('keyword')) {
            $query->where('title', 'like', '%' . $request->keyword . '%')
                  ->orWhere('description', 'like', '%' . $request->keyword . '%');
        }

        $courses = $query->get();

        return response()->json($courses);
    }

    // GET /api/courses/{id}
    public function show($id)
    {
        $course = Course::with(['lessons', 'faqs', 'notices'])->findOrFail($id);
        return response()->json($course);
    }

    // POST /api/courses (Admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'price' => 'nullable|numeric',
            'category_id' => 'nullable|integer',
        ]);

        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    // PUT /api/courses/{id} (Admin)
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'category_id' => 'sometimes|integer',
        ]);

        $course->update($validated);

        return response()->json($course);
    }
}