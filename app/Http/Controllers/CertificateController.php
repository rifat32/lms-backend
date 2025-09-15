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

class CertificateController extends Controller
{
    // POST /api/courses/{id}/complete
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

    // GET /api/certificates/download/{id}
    public function download($id)
    {
        $certificate = Certificate::findOrFail($id);
        $file_path = storage_path("app/public/{$certificate->pdf_url}");

        if (!file_exists($file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($file_path);
    }

    // GET /api/certificates/verify/{code}
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