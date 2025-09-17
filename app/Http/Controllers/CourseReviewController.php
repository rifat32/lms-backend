<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseReview;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;


/**
 * @OA\Tag(
 *     name="CourseReviews",
 *     description="Endpoints for managing course reviews"
 * )
 */
class CourseReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="courses/{id}/reviews",
     *     tags={"CourseReviews"},
     *     summary="Get all approved reviews for a course",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of approved reviews",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="review_id", type="integer", example=1),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="comment", type="string", example="Great course!"),
     *                 @OA\Property(property="user_name", type="string", example="John Doe"),
     *                 @OA\Property(property="created_at", type="string", example="2025-09-16 12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
    public function index($id)
    {
        $course = Course::findOrFail($id);

        $reviews = CourseReview::with('user')
            ->where('course_id', $course->id)
            ->where('status', 'approved') // only show approved reviews
            ->get()
            ->map(function ($review) {
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

    /**
     * @OA\Post(
     *     path="courses/{id}/reviews",
     *     tags={"CourseReviews"},
     *     summary="Submit a review for a course",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating","comment"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Excellent course!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review submitted for moderation",
     *         @OA\JsonContent(
     *             @OA\Property(property="review_id", type="integer", example=10),
     *             @OA\Property(property="message", type="string", example="Review submitted for moderation.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
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
