<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseReview;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class CourseReviewController extends Controller
{
    // GET /api/courses/{id}/reviews
    public function index($id)
    {
        $course = Course::findOrFail($id);

        $reviews = CourseReview::with('user')
            ->where('course_id', $course->id)
            ->where('status', 'approved') // only show approved reviews
            ->get()
            ->map(function($review) {
                return [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user_name' => $review->user->name ?? 'Anonymous',
                    'created_at' => $review->created_at
                ];
            });

        return response()->json($reviews);
    }

    // POST /api/courses/{id}/reviews
    public function store(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $course = Course::findOrFail($id);

        $review = CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending', // for moderation
        ]);

        return response()->json([
            'review_id' => $review->id,
            'message' => 'Review submitted for moderation.'
        ], 201);
    }
}