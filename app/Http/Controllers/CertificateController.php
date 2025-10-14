<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Rules\ValidCourse;
use App\Rules\ValidUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PDF; // Use barryvdh/laravel-dompdf

/**
 * @OA\Tag(
 *     name="Certificates",
 *     description="Endpoints for generating, downloading, and verifying certificates"
 * )
 */
class CertificateController extends Controller
{


    /**
     * @OA\Get(
     *     path="/v1.0/certificate-template",
     *     operationId="getCertificateTemplate",
     *     tags={"Certificates"},
     *     summary="Get the currently active certificate template (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active certificate template retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Default Certificate Template"),
     *                 @OA\Property(property="html_content", type="string", example="<div>...</div>"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active certificate template found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No active certificate template found")
     *         )
     *     )
     * )
     */
    public function getCertificateTemplate()
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $template = CertificateTemplate::where('is_active', true)->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'No active certificate template found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/v1.0/certificate-template/{id}",
     *     operationId="getCertificateTemplateById",
     *     tags={"Certificates"},
     *     summary="Get the currently active certificate template (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Certificate ID",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Active certificate template retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Default Certificate Template"),
     *                 @OA\Property(property="html_content", type="string", example="<div>...</div>"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active certificate template found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No active certificate template found")
     *         )
     *     )
     * )
     */
    public function getCertificateTemplateById(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $template = CertificateTemplate::findOrFail($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'No active certificate template found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificate template retrieved successfully',
            'data' => $template
        ], 200);
    }


    /**
     * @OA\Put(
     *     path="/v1.0/certificate-template/{id}",
     *     operationId="updateCertificateTemplate",
     *     tags={"Certificates"},
     *     summary="Update an existing certificate template (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Certificate Template ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Certificate Template"),
     *             @OA\Property(property="html_content", type="string", example="<div>Updated HTML</div>"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Certificate template updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Certificate template updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Certificate Template"),
     *                 @OA\Property(property="html_content", type="string", example="<div>Updated HTML</div>"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Template not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Template not found")
     *         )
     *     )
     * )
     */
    public function updateCertificateTemplate(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = CertificateTemplate::find($id);
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        // if is_active = true, deactivate others
        if ($request->has('is_active') && $request->is_active) {
            CertificateTemplate::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $template->update($request->only(['name', 'html_content', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Certificate template updated successfully',
            'data' => $template
        ], 200);
    }


    /**
     * @OA\Put(
     *     path="/v1.0/certificates/generate-dynamic",
     *     operationId="generateDynamicCertificate",
     *     tags={"Certificates"},
     *     summary="Generate a professional dynamic certificate PDF (no saving) (role: Any Role)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "course_id"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=101)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns a generated certificate PDF",
     *         @OA\MediaType(mediaType="application/pdf")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Course not completed yet or no template found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not completed yet.")
     *         )
     *     )
     * )
     */
    public function generateDynamicCertificate(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $user = User::findOrFail($request->user_id);
        $course = Course::findOrFail($request->course_id);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment || $enrollment->progress < 100) {
            return response()->json([
                'success' => false,
                'message' => 'Course not completed yet.'
            ], 400);
        }

        $template = CertificateTemplate::where('is_active', true)->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'No active certificate template found.'
            ], 404);
        }

        // 🪄 Replace placeholders
        $certificate_code = strtoupper(Str::random(10));
        $issued_date = now()->format('F d, Y');
        $html = str_replace(
            ['{user_name}', '{course_name}', '{issued_date}', '{certificate_code}'],
            [$user->name, $course->title, $issued_date, $certificate_code],
            $template->html_content
        );

        // ✨ Wrap with an optional professional background and styling
        $final_html = '
    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f0f8ff, #ffffff); padding: 60px;">
        <div style="max-width: 800px; margin: auto; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.15); border-radius: 12px;">
            ' . $html . '
            <p style="margin-top: 40px; font-size: 12px; color: #777;">This certificate is generated digitally and does not require a signature.</p>
        </div>
    </div>
    ';

        // 🧾 Generate and stream PDF
        $pdf = Pdf::loadHTML($final_html)->setPaper('a4', 'landscape');
        return $pdf->stream("certificate_{$certificate_code}.pdf");
    }
}
