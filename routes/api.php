<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseFaqController;
use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\CustomWebhookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\QuestionCategoryController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\TrashController;
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
    Route::post('/verify-user-email', [AuthController::class, "verifyUserEmail"]);
    Route::post('/verify-business-email', [AuthController::class, "verifyBusinessEmail"]);
});



// PRIVATE ROUTES
Route::middleware('auth:api')->group(function () {
    // VERIFY USER BY TOKEN
    Route::get('/v1.0/verify-user-by-token', [AuthController::class, 'verifyUserByToken']);

    // USER APIS
    Route::prefix('/v1.0/users')->group(function () {
        Route::put('/{id}', [UserController::class, 'updateUser']);
        Route::get('/', [UserController::class, 'getAllUsers']);
        Route::get('/{id}', [UserController::class, 'getUserById']);
        Route::delete('/{ids}', [UserController::class, 'deleteUsers']);
    });

    // NOTIFICATION APIS
    Route::prefix('/v1.0/notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::get('/business/{business_id}', [NotificationController::class, 'getNotificationsByBusinessId']);
        Route::patch('/{id}/status', [NotificationController::class, 'updateNotificationStatus']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllNotificationsAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'deleteNotificationById']);
    });

    // Admin-only routes
    // COURSE CATEGORY
    Route::group(['prefix' => '/v1.0/course-categories'], function () {
        Route::post('/', [CourseCategoryController::class, 'createCourseCategory']);
        Route::put('/', [CourseCategoryController::class, 'updateCourseCategory']);
        Route::get('/', [CourseCategoryController::class, 'getCourseCategory']);
        Route::get('/{id}', [CourseCategoryController::class, 'getCourseCategoryById']);
        Route::delete('/{ids}', [CourseCategoryController::class, 'deleteCourseCategory']);
    });


    Route::put('/v1.0/course-faqs', [CourseFaqController::class, 'updateCourseFaqs']);
    Route::get('/v1.0/course-faqs/{course_id}', [CourseFaqController::class, 'getCourseFaqs']);
    Route::get('/v1.0/course-faqs', [CourseFaqController::class, 'getCourseFaqsAll']);
    Route::delete('/course-faqs/{ids}', [CourseFaqController::class, 'deleteCourseFaqs']);


    // COURSE
    Route::group(['prefix' => '/v1.0/courses'], function () {
        Route::post('/', [CourseController::class, 'createCourse']);
        Route::put('/', [CourseController::class, 'updateCourse']);

        Route::put('/status', [CourseController::class, 'updateCourseStatus']);

        Route::patch('/', [CourseController::class, 'updatePartialCourse']);
        Route::get('/', [CourseController::class, 'getCourses']);
        Route::get('/{id}', [CourseController::class, 'getCourseById']);
        Route::delete('/{ids}', [CourseController::class, 'deleteCourse']);
    });

    // Lesson
    Route::group(['prefix' => '/v1.0/lessons'], function () {
        Route::post('/', [LessonController::class, 'createLesson']);
        Route::put('/', [LessonController::class, 'updateLesson']);
        Route::get('/', [LessonController::class, 'getLessons']);
        Route::get('/{id}', [LessonController::class, 'getLessonById']);
        Route::delete('/{ids}', [LessonController::class, 'deleteLesson']);
    });

    Route::group(['prefix' => '/v1.0/coupons'], function () {
        Route::post('/', [CouponController::class, 'createCoupon']);
        Route::put('/{id}', [CouponController::class, 'updateCoupon']);
        Route::get('/', [CouponController::class, 'getAllCoupons']);
        Route::patch('/{id}/toggle-active', [CouponController::class, 'toggleActiveCoupon']);
        Route::delete('/{id}', [CouponController::class, 'deleteCouponById']);
        Route::post('/apply', [CouponController::class, 'applyCoupon']);
    });


    // sections
    Route::group(['prefix' => '/v1.0/sections'], function () {
        Route::post('/', [SectionController::class, 'createSection']);       // Create
        Route::put('/', [SectionController::class, 'updateSection']);    // Update by ID
        Route::get('/', [SectionController::class, 'getSections']);          // Get all
        Route::get('/{id}', [SectionController::class, 'getSectionById']);   // Get by ID
        Route::delete('/{ids}', [SectionController::class, 'deleteSection']); // 🔥 Delete
    });

    // Business
    Route::post('/v1.0/register-user-with-business', [BusinessController::class, 'registerUserWithBusiness']);
    Route::group(['prefix' => '/v1.0/businesses'], function () {
        Route::put('/{id}', [BusinessController::class, 'updateBusiness']);
        Route::get('/', [BusinessController::class, 'getAllBusinesses']);
        Route::get('/{id}', [BusinessController::class, 'getBusinessById']);
        Route::delete('/{ids}', [BusinessController::class, 'deleteBusiness']);
    });

    Route::group(['prefix' => '/v1.0/payments'], function () {
        Route::post('/intent', [StripePaymentController::class, 'createPaymentIntent']);
        Route::get('/', [StripePaymentController::class, 'getPayments']);
        Route::get('/{id}', [StripePaymentController::class, 'getPaymentDetail']);
        Route::get('/{paymentId}/pay-slip', [StripePaymentController::class, 'downloadPaymentSlip']);
    });

    // QUESTION
    Route::group(['prefix' => '/v1.0/questions'], function () {
        Route::post('/', [QuestionController::class, 'createQuestion']);
        Route::put('/', [QuestionController::class, 'updateQuestion']);
        Route::get('/', [QuestionController::class, 'getAllQuestions']);
        Route::get('/{id}', [QuestionController::class, 'getQuestionById']);
        Route::delete('/{ids}', [QuestionController::class, 'deleteQuestion']);
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


    // QUIZ API
    Route::prefix('/v1.0/quizzes')->group(function () {
        Route::get('/', [QuizController::class, 'getQuizWithQuestions']);
        Route::get('/{id}', [QuizController::class, 'getQuizWithQuestionsById']);
        Route::post('/', [QuizController::class, 'store']);
        Route::put('/', [QuizController::class, 'update']);
        Route::delete('/{id}', [QuizController::class, 'destroy']);
    });

    // Submit quiz attempt
    Route::prefix('/v1.0/quizzes/attempts')->group(function () {
        Route::post('/submit', [QuizAttemptController::class, 'submitQuizAttempt']);
        Route::post('/start', [QuizAttemptController::class, 'startQuizAttempt']);
    });

    // QUESTION CATEGORIES API
    Route::group(['prefix' => '/v1.0/question-categories'], function () {
        Route::post('/', [QuestionCategoryController::class, 'createQuestionCategory']); // Already exists
        Route::put('/', [QuestionCategoryController::class, 'updateQuestionCategory']);  // Update
        Route::get('/', [QuestionCategoryController::class, 'getQuestionCategories']);   // Get all
        Route::delete('/{ids}', [QuestionCategoryController::class, 'deleteQuestionCategory']); // Delete
    });
    Route::get('/v1.0/question-categories/validate-slug', [QuestionCategoryController::class, 'validateSlug']);



    // REPORT API
    Route::group(['prefix' => '/v1.0/reports'], function () {
        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/enrollments', [ReportController::class, 'enrollmentAnalytics']);
        Route::get('/revenue', [ReportController::class, 'revenueReport']);
        Route::get('/overview', [ReportController::class, 'overviewReport']);
        Route::get('/course-performance', [ReportController::class, 'coursePerformanceReport']);
    });



    // quiz attempt
    Route::put('/v1.0/quiz-attempts/grade', [QuizAttemptController::class, 'gradeQuizAttempt']);



    Route::put('/v1.0/sections-with-lessons', [SectionController::class, 'updateSectionWithLessons']);

    Route::put('/v1.0/sections-add-lessons', [SectionController::class, 'updateSectionAddLessons']);

    Route::put('/v1.0/sections-remove-lessons', [SectionController::class, 'updateSectionRemoveLessons']);


    // Enrollments
    Route::post('/v1.0/enrollments', [EnrollmentController::class, 'createEnrollment']);
    Route::get('/v1.0/users/{id}/enrollments', [EnrollmentController::class, 'userEnrollments']);

    // LESSON PROGRESS API
    Route::put('/v1.0/lessons/progress', [LessonProgressController::class, 'updateLessonProgress']);
    Route::put('/v1.0/lessons/time', [LessonProgressController::class, 'trackLessonTime']);




    // CERTIFICATE RELATED API
    Route::get('/v1.0/certificate-template', [CertificateController::class, 'getCertificateTemplate']);
    Route::put('/v1.0/certificate-template/{id}', [CertificateController::class, 'updateCertificateTemplate']);
    Route::get('/v1.0/certificate-template/{id}', [CertificateController::class, 'getCertificateTemplateById']);
    Route::put('/v1.0/certificates/generate-dynamic', [CertificateController::class, 'generateDynamicCertificate']);



    // Generate certificate after completing a course
    Route::post('/v1.0/courses/{id}/complete', [TrashController::class, 'generateCertificate']);

    // Download user's certificate
    Route::get('/v1.0/certificates/download/{id}', [TrashController::class, 'downloadCertificate']);

    // Submit course review
    Route::get('/v1.0/courses/{id}/reviews', [CourseReviewController::class, 'submitCourseReview']);



    // BUSINESS SETTINGS API
    Route::put('/v1.0/business-settings', [SettingController::class, "updateBusinessSettings"]);
    Route::get('/v1.0/business-settings', [SettingController::class, "getBusinessSettings"]);



    Route::get('/v1.0/client/courses/secure/{id}', [CourseController::class, 'getCourseByIdSecureClient']);

    Route::get('/v1.0/client/courses/secure', [CourseController::class, 'getCoursesClientSecure']);

    Route::get('/v1.0/client/quizzes/{id}', [QuizController::class, 'getQuizWithQuestionsByIdClient']);
    // routes/api.php
    Route::get('/v1.0/dashboard', [DashboardController::class, 'index']);

    Route::post('/v1.0/coupons', [CouponController::class, 'createCoupon']);
    Route::patch('/v1.0/coupons/{id}', [CouponController::class, 'updateCoupon']);
    Route::get('/v1.0/coupons', [CouponController::class, 'getAllCoupons']);
    Route::put('/v1.0/coupons/toggle-active', [CouponController::class, 'toggleActiveCoupon']);
    Route::delete('/v1.0/coupons/{id}', [CouponController::class, 'deleteCouponById']);
    Route::patch('/v1.0/student-profile/{id}', [StudentProfileController::class, 'updateStudentProfile']);
});



// Get all reviews (auth optional)
Route::get('/v1.0/courses/{id}/reviews', [CourseReviewController::class, 'getCourseReviews']);



// Public verification route
Route::get('/v1.0/certificates/verify/{code}', [CertificateController::class, 'verifyCertificate']);

Route::post('webhooks/stripe', [CustomWebhookController::class, "handleStripeWebhook"])->name("stripe.webhook");
Route::post('/forget-password', [AuthController::class, "storeToken"]);
Route::patch('/reset-password/{token}', [AuthController::class, "resetPasswordWithToken"]);


// CLIENT APIS 
Route::prefix('/v1.0/client')->group(function () {

    // COURSE
    Route::get('/courses/{id}', [CourseController::class, 'getCourseByIdUnified']);
    Route::get('/courses', [CourseController::class, 'getCoursesClientUnified']);
    Route::get('/courses/{slug}', [CourseController::class, 'getCourseBySlugClient']);

    // COURSE FAQ
    Route::get('/course-faqs/{course_id}', [CourseFaqController::class, 'getCourseFaqsClient']);

    // Get all course categories
    Route::get('/course-categories', [CourseCategoryController::class, 'getCourseCategoryClient']);

    // BUSINESS SETTINGS
    Route::get('/business-settings', [SettingController::class, "getBusinessSettingsClient"]);
});
