<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseFaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CourseFaqController extends Controller
{
      /**
     * @OA\Put(
     *     path="/v1.0/course-faqs",
     *     tags={"course_management.course_faq"},
     *     summary="Add or update FAQs for a course (role: Admin only)",
     *     operationId="updateCourseFaqs",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of FAQs to add or update for a specific course",
     *         @OA\JsonContent(
     *             required={"course_id", "faqs"},
     *             @OA\Property(property="course_id", type="integer", example=10),
     *             @OA\Property(
     *                 property="faqs",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"question", "answer"},
     *                     @OA\Property(property="id", type="integer", example=1, nullable=true, description="Optional for existing FAQ updates"),
     *                     @OA\Property(property="question", type="string", example="What is this course about?"),
     *                     @OA\Property(property="answer", type="string", example="This course covers Laravel basics and advanced concepts.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQs updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course FAQs updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="question", type="string", example="What is this course about?"),
     *                     @OA\Property(property="answer", type="string", example="This course covers Laravel basics.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateCourseFaqs(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'faqs' => 'required|array|min:1',
            'faqs.*.id' => 'nullable|integer|exists:course_faqs,id',
            'faqs.*.question' => 'required|string|max:500',
            'faqs.*.answer' => 'required|string|max:2000',
        ]);

        DB::beginTransaction();

        try {
            $course_id = $validated['course_id'];

            // delete removed FAQs if needed (optional)
            $existing_ids = CourseFaq::where('course_id', $course_id)->pluck('id')->toArray();
            $incoming_ids = collect($validated['faqs'])->pluck('id')->filter()->toArray();
            $to_delete = array_diff($existing_ids, $incoming_ids);
            CourseFaq::whereIn('id', $to_delete)->delete();

            // upsert faqs
            $faqs = collect($validated['faqs'])->map(function ($faq) use ($course_id) {
                return CourseFaq::updateOrCreate(
                    ['id' => $faq['id'] ?? null],
                    [
                        'course_id' => $course_id,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]
                );
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course FAQs updated successfully',
                'data' => $faqs
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course FAQs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * @OA\Get(
     *     path="/v1.0/course-faqs/{course_id}",
     *     tags={"course_management.course_faq"},
     *     summary="Get all FAQs for a specific course (role: Any Role)",
     *     operationId="getCourseFaqs",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="course_id",
     *         in="path",
     *         required=true,
     *         description="The ID of the course to retrieve FAQs for",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQs retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course FAQs retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="question", type="string", example="What will I learn?"),
     *                     @OA\Property(property="answer", type="string", example="You will learn Laravel, PHP, and database integration.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
    public function getCourseFaqs($course_id)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

        $course = Course::find($course_id);
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
            ], 404);
        }

        $faqs = CourseFaq::where('course_id', $course_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Course FAQs retrieved successfully',
            'data' => $faqs,
        ], 200);
    }


}
