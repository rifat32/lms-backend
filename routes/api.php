<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseFaqController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SectionController;
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

    // USER APIS
    Route::prefix('/v1.0/users')->group(function () {
        Route::put('/', [UserController::class, 'updateUser']);
        Route::get('/', [UserController::class, 'getAllUsers']);
        Route::get('/{id}', [UserController::class, 'getUserById']);
        Route::delete('/{ids}', [UserController::class, 'deleteUsers']);
    });


    // Admin-only routes
    // COURSE CATEGORY
    Route::group(['prefix' => '/v1.0/course-categories'], function () {
        Route::post('/', [CourseCategoryController::class, 'createCourseCategory']);
        Route::put('/', [CourseCategoryController::class, 'updateCourseCategory']);
        Route::get('/', [CourseCategoryController::class, 'getCourseCategory']);
        Route::get('/{id}', [CourseCategoryController::class, 'getCourseCategoryById']);
        Route::get('/{ids}', [CourseCategoryController::class, 'deleteCourseCategory']);
    });


    Route::put('/v1.0/course-faqs', [CourseFaqController::class, 'updateCourseFaqs']);
    Route::get('/v1.0/course-faqs/{course_id}', [CourseFaqController::class, 'getCourseFaqs']);
    // COURSE
    Route::group(['prefix' => '/v1.0/courses'], function () {
        Route::post('/', [CourseController::class, 'createCourse']);
        Route::put('/', [CourseController::class, 'updateCourse']);
        Route::get('/', [CourseController::class, 'getCourses']);
        Route::get('/{id}', [CourseController::class, 'getCourseById']);
        Route::delete('/{ids}', [CourseController::class, 'deleteCourse']);
    });

    // Lesson
    Route::group(['prefix' => '/v1.0/lessons'], function () {
        Route::post('/', [LessonController::class, 'createLesson']);
        Route::put('/', [LessonController::class, 'updateLesson']);
        Route::delete('/{ids}', [LessonController::class, 'deleteLesson']);
    });


    // quiz attempt
    Route::put('/v1.0/quiz-attempts/{id}/grade', [QuizAttemptController::class, 'gradeQuizAttempt']);

    // sections
    Route::group(['prefix' => '/v1.0/sections'], function () {
        Route::post('/', [SectionController::class, 'createSection']);       // Create
        Route::put('/', [SectionController::class, 'updateSection']);    // Update by ID

        Route::put('/-with-lessons', [SectionController::class, 'updateSectionWithLessons']); // Update with lessons



        Route::get('/', [SectionController::class, 'getSections']);          // Get all
        Route::get('/{id}', [SectionController::class, 'getSectionById']);   // Get by ID
        Route::delete('/{ids}', [SectionController::class, 'deleteSection']); // 🔥 Delete
    });

    // Business
    Route::post('/v1.0/register-user-with-business', [BusinessController::class, 'registerUserWithBusiness']);
    Route::group(['prefix' => '/v1.0/businesses'], function () {
        Route::post('/', [BusinessController::class, 'createBusiness']);
        Route::put('/', [BusinessController::class, 'updateBusiness']);
        Route::put('/', [BusinessController::class, 'updateBusiness']);
        Route::get('/', [BusinessController::class, 'getAllBusinesses']);
        Route::get('/{id}', [BusinessController::class, 'getBusinessById']);
        Route::delete('/{ids}', [BusinessController::class, 'deleteBusiness']);
    });

    // QUESTION
    Route::group(['prefix' => '/v1.0/questions'], function () {
        Route::post('/', [QuestionController::class, 'createQuestion']);
        Route::put('/', [QuestionController::class, 'updateQuestion']);
        Route::put('/', [QuestionController::class, 'updateQuestion']);
        Route::get('/', [QuestionController::class, 'getAllQuestions']);
        Route::get('/{id}', [QuestionController::class, 'getQuestionById']);
    });

    // QUESTION OPTIONS
    Route::group(['prefix' => '/v1.0/options'], function () {
        Route::post('/', [OptionController::class, 'createOption']);
        Route::put('/', [OptionController::class, 'updateOption']);
        Route::put('/', [OptionController::class, 'updateOption']);
        Route::get('/', [OptionController::class, 'getAllOptions']);
        Route::get('/{id}', [OptionController::class, 'getOptionById']);
        Route::get('/question/{question_id}', [OptionController::class, 'getOptionByQuestionId']);
    });



    // Enrollments
    Route::post('/v1.0/enrollments', [EnrollmentController::class, 'createEnrollment']);
    Route::get('/v1.0/users/{id}/enrollments', [EnrollmentController::class, 'userEnrollments']);

    // Lesson progress
    Route::put('/v1.0/lessons/{id}/progress', [LessonProgressController::class, 'updateLessonProgress']);

    Route::put('/v1.0/lessons/{id}/time', [LessonProgressController::class, 'trackLessonTime']);
    

    // Get quiz with questions
    Route::get('/v1.0/quizzes/{id}', [QuizController::class, 'getQuizWithQuestionsById']);

    // Submit quiz attempt
    Route::post('/v1.0/quizzes/{id}/attempts', [QuizAttemptController::class, 'submitQuizAttempt']);

    Route::post('/v1.0/quizzes/{id}/attempts/start', [QuizAttemptController::class, 'startQuizAttempt']);

    



    // Generate certificate after completing a course
    Route::post('/v1.0/courses/{id}/complete', [CertificateController::class, 'generateCertificate']);

    // Download user's certificate
    Route::get('/v1.0/certificates/download/{id}', [CertificateController::class, 'downloadCertificate']);

    Route::get('/v1.0/courses/{id}/reviews', [CourseReviewController::class, 'submitCourseReview']);

    // Sales report
    Route::get('/v1.0/reports/sales', [ReportController::class, 'sales']);

    // Enrollments report
    Route::get('/v1.0/reports/enrollments', [ReportController::class, 'enrollments']);
});

// Get all reviews (auth optional)
Route::get('/v1.0/courses/{id}/reviews', [CourseReviewController::class, 'getCourseReviews']);



// Public verification route
Route::get('/v1.0/certificates/verify/{code}', [CertificateController::class, 'verifyCertificate']);




  Route::get('/v1.0/client/courses', [CourseController::class, 'getCoursesClient']);

