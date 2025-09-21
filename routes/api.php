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



//  Auth
Route::prefix('/v1.0/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});


// PRIVATE ROUTES
Route::middleware('auth:api')->group(function () {
    Route::get('/v1.0/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/v1.0/users/{id}', [UserController::class, 'getUserById']);
    Route::put('/v1.0/users', [UserController::class, 'updateUser']);

    // Courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {

        // COURSE CATEGORY
        Route::post('/v1.0/course-categories', [CourseCategoryController::class, 'createCourseCategory']);
        Route::put('/v1.0/course-categories', [CourseCategoryController::class, 'updateCourseCategory']);
        Route::get('/v1.0/course-categories', [CourseCategoryController::class, 'getCourseCategory']);
        Route::get('/v1.0/course-categories/{id}', [CourseCategoryController::class, 'getCourseCategoryById']);

        // COURSE
        Route::post('/v1.0/courses', [CourseController::class, 'createCourse']);
        Route::put('/v1.0/courses', [CourseController::class, 'updateCourse']);
        Route::get('/v1.0/courses', [CourseController::class, 'getCourses']);
        Route::get('/v1.0/courses/{id}', [CourseController::class, 'getCourseById']);

        // Lesson
        Route::post('/v1.0/lessons', [LessonController::class, 'createLesson']);
        Route::put('/v1.0/lessons', [LessonController::class, 'updateLesson']);
    });

    // Enrollments
    Route::post('/v1.0/enrollments', [EnrollmentController::class, 'store']);
    Route::get('/v1.0/users/{id}/enrollments', [EnrollmentController::class, 'userEnrollments']);

    // Lesson progress
    Route::put('/v1.0/lessons/{id}/progress', [LessonProgressController::class, 'updateLessonProgress']);


    // Get quiz with questions
    Route::get('/v1.0/quizzes/{id}', [QuizController::class, 'show']);

    // Submit quiz attempt
    Route::post('/v1.0/quizzes/{id}/attempts', [QuizAttemptController::class, 'store']);

    // Admin manual grading
    Route::middleware('role:admin')->put('/v1.0/quiz-attempts/{id}/grade', [QuizAttemptController::class, 'grade']);


    // Generate certificate after completing a course
    Route::post('/v1.0/courses/{id}/complete', [CertificateController::class, 'generate']);

    // Download user's certificate
    Route::get('/v1.0/certificates/download/{id}', [CertificateController::class, 'download']);

    Route::get('/v1.0/courses/{id}/reviews', [CourseReviewController::class, 'store']);

    // Sales report
    Route::get('/v1.0/reports/sales', [ReportController::class, 'sales']);

    // Enrollments report
    Route::get('/v1.0/reports/enrollments', [ReportController::class, 'enrollments']);
});

// Get all reviews (auth optional)
Route::get('/v1.0/courses/{id}/reviews', [CourseReviewController::class, 'index']);



// Public verification route
Route::get('/v1.0/certificates/verify/{code}', [CertificateController::class, 'verify']);
