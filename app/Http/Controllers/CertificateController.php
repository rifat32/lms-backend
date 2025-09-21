<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
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
     * @OA\Post(
     *     path="/v1.0/courses/{id}/complete",
     *     operationId="generateCertificate",
     *     tags={"Certificates"},
     *     summary="Generate a certificate for a completed course",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer", example=101)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","course_id"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=101)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Certificate generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Certificate generated."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="certificate_id", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Course not completed yet",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not completed yet.")
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
     *         description="User, course, or enrollment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The user_id field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="user_id", type="array",
     *                     @OA\Items(type="string", example="The user_id field is required.")
     *                 ),
     *                 @OA\Property(property="course_id", type="array",
     *                     @OA\Items(type="string", example="The course_id field is required.")
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


    public function generateCertificate(Request $request, $id)
    {
        $request->validate([
            'user_id' => ['required', 'integer', new ValidUser()],
            'course_id' => ['required', 'integer', new ValidCourse()],
        ]);

        $user = User::findOrFail($request->user_id);
        $course = Course::findOrFail($request->course_id);

        // Check if user completed the course
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        if ($enrollment->progress < 100) {
            return response()->json(['message' => 'Course not completed yet.'], 400);
        }

        // Generate unique certificate code
        $code = Str::upper(Str::random(10));

        // Generate PDF (simplified example)
        $pdf = PDF::loadView('certificates.template', [
            'user' => $user,
            'course' => $course,
            'code' => $code,
            'issued_at' => now(),
        ]);

        $pdf_path = storage_path("app/public/certificates/{$code}.pdf");
        $pdf->save($pdf_path);

        // Save certificate record
        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'certificate_code' => $code,
            'pdf_url' => "certificates/{$code}.pdf",
            'issued_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Certificate generated.',
            'data' => [
                'certificate_id' => $certificate->id,
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/certificates/download/{id}",
     *     operationId="downloadCertificate",
     *     tags={"Certificates"},
     *     summary="Download a certificate PDF",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Certificate ID",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Certificate PDF download",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid certificate ID")
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
     *         description="File not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="File not found")
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


    public function downloadCertificate($id)
    {
        $certificate = Certificate::findOrFail($id);
        $file_path = storage_path("app/public/{$certificate->pdf_url}");

        if (!file_exists($file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($file_path);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/certificates/verify/{code}",
     *     operationId="verifyCertificate",
     *     tags={"Certificates"},
     *     summary="Verify a certificate by code",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="Certificate code",
     *         @OA\Schema(type="string", example="ABCD123XYZ")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Certificate is valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Certificate is valid"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_valid", type="boolean", example=true),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe")
     *                 ),
     *                 @OA\Property(property="course", type="object",
     *                     @OA\Property(property="title", type="string", example="Laravel Basics")
     *                 ),
     *                 @OA\Property(property="issued_at", type="string", example="2025-09-16 12:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid certificate code")
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
     *         description="Certificate not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Certificate not found"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_valid", type="boolean", example=false)
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

    public function verifyCertificate($code)
    {
        $certificate = Certificate::with('enrollment.user', 'enrollment.course')
            ->where('certificate_code', $code)
            ->first();

        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
                'data' => ['is_valid' => false]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificate is valid',
            'data' => [
                'is_valid' => true,
                'user' => [
                    'name' => $certificate->enrollment->user->name
                ],
                'course' => [
                    'title' => $certificate->enrollment->course->title
                ],
                'issued_at' => $certificate->issued_at,
            ]
        ]);
    }
}
