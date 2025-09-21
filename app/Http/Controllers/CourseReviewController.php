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
     *     path="/v1.0/courses/{id}/reviews",
     *     operationId="getCourseReviews",
     *     tags={"CourseReviews"},
     *     summary="Get all approved reviews for a course",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of approved reviews",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reviews retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="review_id", type="integer", example=1),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="comment", type="string", example="Great course!"),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="created_at", type="string", example="2025-09-16 12:00:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bad request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */

    public function getCourseReviews($id)
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

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => $reviews
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/v1.0/courses/{id}/reviews",
     *     operationId="submitCourseReview",
     *     tags={"CourseReviews"},
     *     summary="Submit a review for a course",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer", example=1)
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review submitted for moderation."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="review_id", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bad request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The rating field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="rating", type="array",
     *                     @OA\Items(type="string", example="The rating field is required.")
     *                 ),
     *                 @OA\Property(property="comment", type="array",
     *                     @OA\Items(type="string", example="The comment field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */

    public function submitCourseReview(Request $request, $id)
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
            'success' => true,
            'message' => 'Review submitted for moderation.',
            'data' =>  ['review_id' => $review->id,]
        ], 201);
    }
}
