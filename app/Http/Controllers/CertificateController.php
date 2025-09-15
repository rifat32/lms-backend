<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
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
     *     path="/api/courses/{id}/complete",
     *     tags={"Certificates"},
     *     summary="Generate a certificate for a completed course",
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
     *             required={"user_id","course_id"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=101)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Certificate generated successfully"),
     *     @OA\Response(response=400, description="Course not completed yet")
     * )
     */
    public function generate(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'course_id' => 'required|exists:courses,course_id',
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
            'certificate_id' => $certificate->id,
            'message' => 'Certificate generated.'
        ]);
    }

      /**
     * @OA\Get(
     *     path="/api/certificates/download/{id}",
     *     tags={"Certificates"},
     *     summary="Download a certificate PDF",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Certificate ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Certificate PDF download"),
     *     @OA\Response(response=404, description="File not found")
     * )
     */
    public function download($id)
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
     *     path="/api/certificates/verify/{code}",
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
     *             @OA\Property(property="is_valid", type="boolean", example=true),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe")
     *             ),
     *             @OA\Property(property="course", type="object",
     *                 @OA\Property(property="title", type="string", example="Laravel Basics")
     *             ),
     *             @OA\Property(property="issued_at", type="string", example="2025-09-16 12:00:00")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Certificate not found")
     * )
     */
    public function verify($code)
    {
        $certificate = Certificate::with('enrollment.user', 'enrollment.course')
            ->where('certificate_code', $code)
            ->first();

        if (!$certificate) {
            return response()->json(['is_valid' => false], 404);
        }

        return response()->json([
            'is_valid' => true,
            'user' => [
                'name' => $certificate->enrollment->user->name
            ],
            'course' => [
                'title' => $certificate->enrollment->course->title
            ],
            'issued_at' => $certificate->issued_at,
        ]);
    }
}