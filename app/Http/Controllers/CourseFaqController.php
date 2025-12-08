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
            'faqs' => 'present|array',
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
    /**
     * @OA\Get(
     *     path="/v1.0/client/course-faqs/{course_id}",
     *     tags={"course_management.course_faq"},
     *     summary="Get all FAQs for a specific course (role: Any Role)",
     *     operationId="getCourseFaqsClient",
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
    public function getCourseFaqsClient($course_id)
    {

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

    /**
     * @OA\Get(
     *     path="/v1.0/course-faqs",
     *     tags={"course_management.course_faq"},
     *     summary="Get FAQs for multiple courses (role: Any Role)",
     *     operationId="getCourseFaqsAll",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="course_ids[]",
     *         in="query",
     *         required=true,
     *         description="Array of course IDs",
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="integer", example=10)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="FAQs retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    public function getCourseFaqsAll(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $course_ids = $request->query('course_ids');

        $faqs = CourseFaq::query()
            ->when(!empty($course_ids), function ($q) use ($course_ids) {
                $ids = is_array($course_ids) ? $course_ids : explode(',', $course_ids);
                $q->whereIn('course_id', $ids);
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Course FAQs retrieved successfully',
            'data' => $faqs,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/course-faqs/{ids}",
     *     operationId="deleteCourseFaqs",
     *     tags={"course_management.course_faq"},
     *     summary="Delete course FAQs (role: Admin/Owner/Lecturer)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="FAQ IDs (comma-separated for multiple)",
     *         @OA\Schema(type="string", example="5,6,7")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="FAQs deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course FAQs deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Some IDs not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Some data not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     )
     * )
     */

    public function deleteCourseFaqs($ids)
    {
        try {

            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            DB::beginTransaction();

            $ids_array = array_map('intval', explode(',', $ids));

            $faqs = CourseFaq::whereIn('id', $ids_array)->get();
            $existing_ids = $faqs->pluck('id')->toArray();

            // --- Check if any FAQ IDs are missing ---
            if (count($existing_ids) !== count($ids_array)) {
                $missing_ids = array_diff($ids_array, $existing_ids);
                DB::rollBack();
                return response()->json([
                    'success'   => false,
                    'message'   => 'Some data not found',
                    'data'      => ['missing_ids' => array_values($missing_ids)]
                ], 400);
            }

            // --- Safe Delete ---
            CourseFaq::whereIn('id', $existing_ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course FAQ(s) deleted successfully',
                'data'    => $existing_ids
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
