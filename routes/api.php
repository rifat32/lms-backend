<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    // Courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {

        // COURSE CATEGORY
        Route::post('/v1/course-categories', [CourseCategoryController::class, 'createCourseCategory']);
        Route::put('/v1/course-categories/{id}', [CourseCategoryController::class, 'updateCourseCategory']);
        Route::get('/v1/course-categories', [CourseCategoryController::class, 'getCourseCategory']);
        Route::get('/v1/course-categories/{id}', [CourseCategoryController::class, 'getCourseCategoryById']);

        // COURSE
        Route::post('/v1/courses', [CourseController::class, 'createCourse']);
        Route::put('/v1/courses/{id}', [CourseController::class, 'updateCourse']);
        Route::get('/v1/courses', [CourseController::class, 'getCourses']);
        Route::get('/v1/courses/{id}', [CourseController::class, 'getCourseById']);

        // Lesson
        Route::post('/courses/{course_id}/lessons', [LessonController::class, 'store']);
        Route::put('/lessons/{id}', [LessonController::class, 'update']);
    });

    // Enrollments
    Route::post('/enrollments', [EnrollmentController::class, 'store']);
    Route::get('/users/{id}/enrollments', [EnrollmentController::class, 'userEnrollments']);

    // Lesson progress
    Route::put('/lessons/{id}/progress', [LessonProgressController::class, 'update']);


    // Get quiz with questions
    Route::get('/quizzes/{id}', [QuizController::class, 'show']);

    // Submit quiz attempt
    Route::post('/quizzes/{id}/attempts', [QuizAttemptController::class, 'store']);

    // Admin manual grading
    Route::middleware('role:admin')->put('/quiz-attempts/{id}/grade', [QuizAttemptController::class, 'grade']);


    // Generate certificate after completing a course
    Route::post('/courses/{id}/complete', [CertificateController::class, 'generate']);

    // Download user's certificate
    Route::get('/certificates/download/{id}', [CertificateController::class, 'download']);

    Route::get('/courses/{id}/reviews', [CourseReviewController::class, 'store']);

    // Sales report
    Route::get('/reports/sales', [ReportController::class, 'sales']);

    // Enrollments report
    Route::get('/reports/enrollments', [ReportController::class, 'enrollments']);
});

// Get all reviews (auth optional)
Route::get('/courses/{id}/reviews', [CourseReviewController::class, 'index']);



// Public verification route
Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify']);
