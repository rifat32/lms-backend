<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoursePartialRequest;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\Section;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Courses",
 *     description="Endpoints for managing courses"
 * )
 */
class CourseController extends Controller
{


/**
 * @OA\Get(
 *     path="/v1.0/client/courses/{id}",
 *     tags={"course_management.course"},
 *     operationId="getCourseByIdUnified",
 *     summary="Get a course by ID (Public or Authenticated)",
 *     description="For guests, shows limited info. For authenticated students enrolled in the course, shows full details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Course ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Course retrieved successfully")
 * )
 */
public function getCourseByIdUnified($id)
{
    $user = auth('api')->user();

    if($user){
         Auth::login($user);
    }

    $query = Course::with([
        'categories',
        'sections.sectionables.sectionable',
        'reviews',
        'enrollment'
    ])
    ->where('status', 'published')
    ->filters();


    $course = $query->find($id);

    if (!$course) {
        return response()->json(['success' => false, 'message' => 'Course not found'], 404);
    }

    // For enrolled users, load more details
    if ($user && $course->enrollment()->where('user_id', $user->id)->exists()) {
        $course->sections->each(function ($section) {
            $section->sectionables->each(function ($sectionable, $index) {

                if ($sectionable->sectionable_type === Lesson::class) {
                    $sectionable->sectionable->load('lesson_progress');
                }
                if ($sectionable->sectionable_type === Quiz::class) {
                    $sectionable->sectionable->load(['questions.options',"quiz_attempts"]);
                }
            });
        });
    }

    return response()->json([
        'success' => true,
        'message' => 'Course retrieved successfully',
        'data' => $course
    ], 200);
}


/**
 * @OA\Get(
 *     path="/v1.0/client/courses",
 *     tags={"course_management.course"},
 *     operationId="getCoursesClientUnified",
 *     summary="Get all courses (Public and Authenticated users)",
 *     description="Retrieve all courses. If user is logged in, filter by enrolled status or show personalized data.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="is_enrolled",
 *         in="query",
 *         required=false,
 *         description="1 for enrolled, 0 for not enrolled",
 *         @OA\Schema(type="string", default="", example="")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of courses"
 *     )
 * )
 */
public function getCoursesClientUnified(Request $request)
{


    $user = auth('api')->user();

         if($user){
         Auth::login($user);
    }

  $query = Course::with([
        'categories:id,name',
        'sections.sectionables.sectionable:id,title',
        'reviews',
        "enrollment"
    ])
    ->where('status', 'published')
    ->filters();


    if ($user) {

        // If filter by enrollment
        if ($request->has('is_enrolled')) {
            $is_enrolled = $request->boolean('is_enrolled');
            $query->whereHas('enrollment', function ($q) use ($user, $is_enrolled) {
                if ($is_enrolled) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->where('user_id', '!=', $user->id);
                }
            });
        }
    }

    $courses = retrieve_data($query, 'created_at', 'courses');
    $courses['data']->each(fn($c) => $c->categories->makeHidden('pivot'));

    $summary = [];



    if(auth()->user()->id) {
    $summary["enrolled_courses_count"] = Course::whereHas("enrollment")
    ->count();

 $summary["completed_courses_count"] = Course::whereHas("enrollment", function($query) {
        $query->where("enrollments.progress",100);
    })
    ->count();


    $lesson_progress_seconds = LessonProgress::where(
        [
            "user_id" => auth()->user()->id,
        ]
    )
    ->sum("total_time_spent");




// Get all quizzes that the user has attempted
$quizzes = Quiz::whereHas('quiz_attempts')
    ->get(['id', 'time_limit', 'time_unit']);

// Convert quiz time limits to seconds
$quiz_seconds = $quizzes->sum(function ($quiz) {
    if ($quiz->time_unit === Quiz::TIME_UNITS['HOURS']) {
        return $quiz->time_limit * 3600; // 1 hour = 3600 seconds
    } elseif ($quiz->time_unit === Quiz::TIME_UNITS['MINUTES']) {
        return $quiz->time_limit * 60;   // 1 minute = 60 seconds
    }
    return 0;
});

$total_learning_seconds = $lesson_progress_seconds + $quiz_seconds;

$summary["total_learning_seconds"] = $total_learning_seconds;



    }

    return response()->json([
        'success' => true,
        'message' => 'Courses retrieved successfully',
        'meta' => $courses['meta'],
        'data' => $courses['data'],
        'summary' => $summary
    ], 200);
}









    /**
     * @OA\Get(
     *     path="trashed/v1.0/client/courses",
     *     tags={"Trash"},
     *     operationId="getCoursesClient",

     *  *     summary="Get all courses (Public - No authentication required) (role: Student only)",
 *     description="Retrieve all courses for non-logged in users. Never use for logged in users.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *     @OA\Parameter(
     *         name="searchKey",
     *         in="query",
     *         required=false,
     *         description="Search by keyword in title only",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Course status only: draft, published, archived",
     *         @OA\Schema(type="string", default="", example="")
     *     ),
     *    *     @OA\Parameter(
     *         name="is_enrolled",
     *         in="query",
     *         required=false,
     *         description="Filter by enrollment status: 1 for enrolled, 0 for not enrolled",
     *         @OA\Schema(type="string", default="", example="")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Courses retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-19T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid query parameters")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource.")
     *         )
     *     )
     * )
     */

    public function getCoursesClient(Request $request)
    {

        $query = Course::with([
            'categories',
            'sections' => function ($q) {
                $q
                    ->with([
                        "sectionables" => function ($sq) {
                            $sq->with([
                                'sectionable' => function ($ssq) {
                                    $ssq->select('id', 'title');
                                }
                            ]);
                        }
                    ]);
            },
            'reviews',
        ])
            ->filters();

        //
        $courses = retrieve_data($query, 'created_at', 'courses');

        // Remove pivot from all categories
        $courses['data'] = $courses['data']->each(function ($course) {
            return $course->categories->makeHidden('pivot');
        });

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully tttttt',
            'meta' => $courses['meta'],
            'data' => $courses['data'],
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="trashed/v1.0/client/courses/{id}",
     *     tags={"Trash"},
     *     operationId="getCourseByIdClient",
     *  *     summary="Get a single course by ID (Public or Non-enrolled users) (role: Student only)",
 *     description="Retrieve a course by its ID for non-logged in users OR for logged-in users viewing a non-enrolled course",

     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Introduction to Programming"),
     *                 @OA\Property(property="description", type="string", example="A beginner course on programming"),
     *                 @OA\Property(
     *                     property="lessons",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Lesson 1")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="faqs",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="question", type="string", example="What is a variable?"),
     *                         @OA\Property(property="answer", type="string", example="A variable is...")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="notices",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Exam Notice"),
     *                         @OA\Property(property="description", type="string", example="Exam will be held on...")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     )
     * )
     */

    public function getCourseByIdClient($id)
    {



        $course = Course::with([
            'categories',
            'sections' => function ($q) {
                $q->with([
                    'sectionables' => function ($sq) {
                        $sq->with([
                            'sectionable'
                        ]);
                    },
                ]);
            },
            'reviews',
        ])->find($id);

        // SEND RESPONSE
        if (empty($course)) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found by'
            ], 404);
        }






        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully',
            'data' => $course
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/v1.0/client/courses/secure/{id}",
     *     tags={"Trash"},
     *     operationId="getCourseByIdSecureClient",
     *  *     summary="Get enrolled course details (Authenticated and enrolled users only) (role: Student only)",
 *     description="Retrieve detailed course information including lessons, quizzes, and progress.
 *     Authenticated and enrolled users only",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Introduction to Programming"),
     *                 @OA\Property(property="description", type="string", example="A beginner course on programming"),
     *                 @OA\Property(
     *                     property="lessons",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Lesson 1")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="faqs",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="question", type="string", example="What is a variable?"),
     *                         @OA\Property(property="answer", type="string", example="A variable is...")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="notices",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Exam Notice"),
     *                         @OA\Property(property="description", type="string", example="Exam will be held on...")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     )
     * )
     */

    public function getCourseByIdSecureClient($id)
    {
        if (!auth()->user()->hasAnyRole(['student'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        // FIND BY ID
        $course = Course::with(
            [
                'categories',
                'sections' => function ($q) {
                    $q
                        ->with([
                            "sectionables" => function ($sq) {
                                $sq->with([
                                    'sectionable'
                                ]);
                            }
                        ]);
                },
                'reviews',
            ]
        )
            ->restrictBeforeEnrollment()
            ->find($id);



        // SEND RESPONSE
        if (empty($course)) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found by'
            ], 404);
        }



        $course->sections->each(function ($section) {
            $section->sectionables->each(function ($sectionable, $index) {


                // if Lesson → load lesson_progress
                if ($sectionable->sectionable_type === Lesson::class) {
                    $sectionable->sectionable->load('lesson_progress');
                }

                // load questions only for quizzes
                if ($sectionable->sectionable_type === Quiz::class) {
                    $sectionable->sectionable->load([
                        'questions.options'
                    ]);
                } else {
                    $sectionable->sectionable->setRelation('questions', collect());
                }
            });
        });


        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully',
            'data' => $course
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/client/courses/secure",
     *     tags={"Trash"},
     *     operationId="getCoursesClientSecure",
     *     security={{"bearerAuth":{}}},
     *
     *     summary="Get all courses (Authenticated users only) (role: Student only)",
     *     description="Retrieve courses for logged-in users only.",
     *
     *
     *
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *    *    *     @OA\Parameter(
     *         name="is_enrolled",
     *         in="query",
     *         required=false,
     *         description="Filter by enrollment status: 1 for enrolled, 0 for not enrolled",
     *         @OA\Schema(type="string", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="searchKey",
     *         in="query",
     *         required=false,
     *         description="Search by keyword in title only",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Course status only: draft, published, archived",
     *         @OA\Schema(type="string", default="", example="")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Courses retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-19T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid query parameters")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource.")
     *         )
     *     )
     * )
     */

    public function getCoursesClientSecure(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['student'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $query = Course::with([
            'categories' => function ($q) {
                $q->select('course_categories.id', 'course_categories.name');
            },
            "enrollment"
        ])->whereHas('enrollment')
            ->filters();

        $courses = retrieve_data($query, 'created_at', 'courses');


        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'meta' => $courses['meta'],
            'data' => $courses['data'],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/courses",
     *     tags={"course_management.course"},
     *     operationId="getCourses",
     *     summary="Get all courses (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Filter by category ID",
     *         @OA\Schema(type="integer", example="")
     *     ),
     *    *    *     @OA\Parameter(
     *         name="is_enrolled",
     *         in="query",
     *         required=false,
     *         description="Filter by enrollment status: 1 for enrolled, 0 for not enrolled",
     *         @OA\Schema(type="string", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="searchKey",
     *         in="query",
     *         required=false,
     *         description="Search by keyword in title only",
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default="", example="")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Course status only: draft, published, archived",
     *         @OA\Schema(type="string", default="", example="")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of courses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Courses retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel Basics"),
     *                     @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.99),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-19T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-19T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid query parameters")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You do not have permission to access this resource.")
     *         )
     *     )
     * )
     */

    public function getCourses(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $query = Course::with(['categories' => function ($q) {
            $q->select('course_categories.id', 'course_categories.name');
        }])->filters();

        $courses = retrieve_data($query, 'created_at', 'courses');

        // Remove pivot from all categories
        $courses['data'] = $courses['data']->each(function ($course) {
            return $course->categories->makeHidden('pivot');
        });

        $summary = [];


        $summary["total_courses_count"] = Course::get()->count();

     // Loop through statuses and count each
foreach (Course::STATUS as $status_key => $status_value) {
    $summary["{$status_value}_count"] = Course::where('status', $status_value)->count();
}

 $summary["total_enrollments_count"] = Enrollment::get()->count();


        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'meta' => $courses['meta'],
            'data' => $courses['data'],
            "summary" => $summary
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/courses/{id}",
     *     tags={"course_management.course"},
     *     operationId="getCourseById",
     *     summary="Get a single course by ID (role: Admin only)",
     *     description="Retrieve a course by its ID along with lessons, FAQs, and notices",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Course ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Introduction to Programming"),
     *                 @OA\Property(property="description", type="string", example="A beginner course on programming"),
     *                 @OA\Property(
     *                     property="lessons",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Lesson 1")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="faqs",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="question", type="string", example="What is a variable?"),
     *                         @OA\Property(property="answer", type="string", example="A variable is...")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="notices",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Exam Notice"),
     *                         @OA\Property(property="description", type="string", example="Exam will be held on...")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     )
     * )
     */

    public function getCourseById($id)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "message" => "You can not perform this action"
            ], 401);
        }

        $course = Course::with([
            'categories',
            'sections' => function ($q) {
                $q->with([
                    'sectionables' => function ($sq) {
                        $sq->with([
                            'sectionable'
                        ]);
                    },
                ]);
            },
            'reviews',
        ])->find($id);

        // SEND RESPONSE
        if (empty($course)) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found by'
            ], 404);
        }
        // ✅ Now eager-load questions only for quizzes (manually)
        $course->sections->each(function ($section) {
            $section->sectionables->each(function ($sectionable) {
                if ($sectionable->sectionable_type == Quiz::class) {
                    $sectionable->sectionable->load('questions');
                } else {
                    $sectionable->sectionable->setRelation('questions', collect()); // empty for lessons
                }
            });
        });


        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully',
            'data' => $course
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/v1.0/courses",
     *     tags={"course_management.course"},
     *     operationId="createCourse",
     *     summary="Create a new course (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","category_id"},
     *             @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="title", type="string", example="Laravel Basics"),
     *         @OA\Property(property="description", type="string", example="A complete Laravel course"),
     *         @OA\Property(property="price", type="number", format="float", example=49.99),
     *         @OA\Property(property="sale_price", type="number", format="float", example=29.99),
     *         @OA\Property(property="price_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="price_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="status", type="string", enum={"draft","published","archived"}, example="draft"),
     *         @OA\Property(property="status_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="status_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="url", type="string", example="https://example.com/laravel-basics"),
     *         @OA\Property(property="level", type="string", example="Beginner"),
     *         @OA\Property(property="cover", type="string", example=""),
     *         @OA\Property(property="preview_video_source_type", type="string", enum={"HTML","YouTube","Vimeo","External Link","Embed"}, example="YouTube"),
     *         @OA\Property(property="preview_video_url", type="string", example="https://youtu.be/example"),
     *         @OA\Property(property="preview_video_poster", type="string", example="poster.jpg"),
     *         @OA\Property(property="preview_video_embed", type="string", example="<iframe src='https://example.com/embed'></iframe>"),
     *         @OA\Property(property="duration", type="string", example="8 hours"),
     *         @OA\Property(property="video_duration", type="string", example="2 hours"),
     *         @OA\Property(property="course_preview_description", type="string", example="This is a preview of the Laravel Basics course."),
     *         @OA\Property(property="is_featured", type="boolean", example=true),
     *         @OA\Property(property="is_lock_lessons_in_order", type="boolean", example=true),
     *         @OA\Property(property="created_by", type="integer", example=1),
     *         @OA\Property(
     *             property="category_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             description="Array of category IDs for this course"
     *         )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="title", type="string", example="Laravel Basics"),
     *             @OA\Property(property="description", type="string", example="Learn Laravel framework"),
     *             @OA\Property(property="price", type="number", format="float", example=49.99),
     *             @OA\Property(property="status", type="string", example="draft"),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="created_by", type="integer", example=1),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-18T12:00:00Z"),
     * *         @OA\Property(
     *             property="category_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             description="Array of category IDs for this course"
     *         )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A course with this title already exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array", @OA\Items(type="string", example="The title field is required.")),
     *                 @OA\Property(property="description", type="array", @OA\Items(type="string", example="The description field is required.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="The category id field is required."))
     *             )
     *         )
     *     )
     * )
     */



    public function createCourse(CourseRequest $request)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            DB::beginTransaction();

            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // ADD CREATED BY
            $request_payload['created_by'] = auth()->user()->id;

            // CREATE COURSE FIRST
            $course = Course::create($request_payload);

            // ========================
            // HANDLE COVER (SINGLE FILE)
            // ========================
            $cover_filename = null;
            $folder_path = "business_1/course_{$course->id}";

            // If uploaded file
            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $cover_filename = $file->hashName(); // consistent naming like lesson
                $file->storeAs($folder_path, $cover_filename, 'public');
            }

            // If existing file path string
            if ($request->filled('cover') && is_string($request->input('cover'))) {
                $cover_filename = basename($request->input('cover'));
            }

            if ($cover_filename) {
                $course->cover = $cover_filename;
                $course->save();
            }

            // ========================
            // SYNC CATEGORIES
            // ========================
            if (!empty($request_payload['category_ids']) && is_array($request_payload['category_ids'])) {
                $course->categories()->sync($request_payload['category_ids']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => $course
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'data' => [
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1.0/courses",
     *     tags={"course_management.course"},
     *     operationId="updateCourse",
     *     summary="Update a course (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","title","category_id"},
     *             @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="title", type="string", example="Laravel Basics"),
     *         @OA\Property(property="description", type="string", example="A complete Laravel course"),
     *         @OA\Property(property="price", type="number", format="float", example=49.99),
     *         @OA\Property(property="sale_price", type="number", format="float", example=29.99),
     *         @OA\Property(property="price_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="price_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="status", type="string", enum={"draft","published","archived"}, example="draft"),
     *         @OA\Property(property="status_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="status_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="url", type="string", example="https://example.com/laravel-basics"),
     *         @OA\Property(property="level", type="string", example="Beginner"),
     *         @OA\Property(property="cover", type="string", example=""),
     *         @OA\Property(property="preview_video_source_type", type="string", enum={"HTML","YouTube","Vimeo","External Link","Embed"}, example="YouTube"),
     *         @OA\Property(property="preview_video_url", type="string", example="https://youtu.be/example"),
     *         @OA\Property(property="preview_video_poster", type="string", example="poster.jpg"),
     *         @OA\Property(property="preview_video_embed", type="string", example="<iframe src='https://example.com/embed'></iframe>"),
     *         @OA\Property(property="duration", type="string", example="8 hours"),
     *         @OA\Property(property="video_duration", type="string", example="2 hours"),
     *         @OA\Property(property="course_preview_description", type="string", example="This is a preview of the Laravel Basics course."),
     *         @OA\Property(property="is_featured", type="boolean", example=true),
     *         @OA\Property(property="is_lock_lessons_in_order", type="boolean", example=true),
     *         @OA\Property(property="created_by", type="integer", example=1),
     *         @OA\Property(
     *             property="category_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             description="Array of category IDs for this course"
     *         )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course updated successfully",
     *         @OA\JsonContent(

     *             @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="title", type="string", example="Laravel Basics"),
     *         @OA\Property(property="description", type="string", example="A complete Laravel course"),
     *         @OA\Property(property="price", type="number", format="float", example=49.99),
     *         @OA\Property(property="sale_price", type="number", format="float", example=29.99),
     *         @OA\Property(property="price_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="price_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="status", type="string", enum={"draft","published","archived"}, example="draft"),
     *         @OA\Property(property="status_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="status_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="url", type="string", example="https://example.com/laravel-basics"),
     *         @OA\Property(property="level", type="string", example="Beginner"),
     *         @OA\Property(property="cover", type="string", example=""),
     *         @OA\Property(property="preview_video_source_type", type="string", enum={"HTML","YouTube","Vimeo","External Link","Embed"}, example="YouTube"),
     *         @OA\Property(property="preview_video_url", type="string", example="https://youtu.be/example"),
     *         @OA\Property(property="preview_video_poster", type="string", example="poster.jpg"),
     *         @OA\Property(property="preview_video_embed", type="string", example="<iframe src='https://example.com/embed'></iframe>"),
     *         @OA\Property(property="duration", type="string", example="8 hours"),
     *         @OA\Property(property="video_duration", type="string", example="2 hours"),
     *         @OA\Property(property="course_preview_description", type="string", example="This is a preview of the Laravel Basics course."),
     *         @OA\Property(property="is_featured", type="boolean", example=true),
     *         @OA\Property(property="is_lock_lessons_in_order", type="boolean", example=true),
     *         @OA\Property(property="created_by", type="integer", example=1),
     *         @OA\Property(
     *             property="category_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             description="Array of category IDs for this course"
     *         )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A course with this title already exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array", @OA\Items(type="string", example="The title field is required.")),
     *                 @OA\Property(property="description", type="array", @OA\Items(type="string", example="The description field is required.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="The category id field is required."))
     *             )
     *         )
     *     )
     * )
     */



    public function updateCourse(CourseRequest $request)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            DB::beginTransaction();

            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // FIND COURSE
            $course = Course::findOrFail($request_payload['id']);

              // ========================
            // UPDATE COURSE
            // ========================
            $request_payload['cover'] = $request_payload['cover'] ?? null;

            $course->update($request_payload);



            // ========================
            // HANDLE COVER (SINGLE FILE)
            // ========================
            $cover_filename = $course->getRawOriginal('cover');
            $folder_path = "business_1/course_{$course->id}";

            // If uploaded file
            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $new_filename = $file->hashName();
                $file->storeAs($folder_path, $new_filename, 'public');

                // Delete old cover if exists
                if ($cover_filename) {
                    $old_path = "{$folder_path}/{$cover_filename}";
                    if (Storage::disk('public')->exists($old_path)) {
                        Storage::disk('public')->delete($old_path);
                    }
                }

                $cover_filename = $new_filename;
            }

            // If string file path provided instead of upload
            if ($request->filled('cover') && is_string($request->input('cover'))) {
                $cover_filename = basename($request->input('cover'));
            }

            $course->cover = $cover_filename;
            $course->save();

            // ========================
            // SYNC CATEGORIES
            // ========================
            if (!empty($request_payload['category_ids']) && is_array($request_payload['category_ids'])) {
                $course->categories()->sync($request_payload['category_ids']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => $course
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'data' => [
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]
            ], 500);
        }
    }



    /**
     * @OA\Patch(
     *     path="/v1.0/courses",
     *     tags={"course_management.course"},
     *     operationId="updatePartialCourse",
     *     summary="Update a course (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id"},
     *             @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="title", type="string", example="Laravel Basics"),
     *         @OA\Property(property="description", type="string", example="A complete Laravel course"),
     *         @OA\Property(property="price", type="number", format="float", example=49.99),
     *         @OA\Property(property="sale_price", type="number", format="float", example=29.99),
     *         @OA\Property(property="price_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="price_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="status", type="string", enum={"draft","published","archived"}, example="draft"),
     *         @OA\Property(property="status_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="status_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="url", type="string", example="https://example.com/laravel-basics"),
     *         @OA\Property(property="level", type="string", example="Beginner"),
     *         @OA\Property(property="cover", type="string", example=""),
     *         @OA\Property(property="preview_video_source_type", type="string", enum={"HTML","YouTube","Vimeo","External Link","Embed"}, example="YouTube"),
     *         @OA\Property(property="preview_video_url", type="string", example="https://youtu.be/example"),
     *         @OA\Property(property="preview_video_poster", type="string", example="poster.jpg"),
     *         @OA\Property(property="preview_video_embed", type="string", example="<iframe src='https://example.com/embed'></iframe>"),
     *         @OA\Property(property="duration", type="string", example="8 hours"),
     *         @OA\Property(property="video_duration", type="string", example="2 hours"),
     *         @OA\Property(property="course_preview_description", type="string", example="This is a preview of the Laravel Basics course."),
     *         @OA\Property(property="is_featured", type="boolean", example=true),
     *         @OA\Property(property="is_lock_lessons_in_order", type="boolean", example=true),
     *         @OA\Property(property="created_by", type="integer", example=1),
     *         @OA\Property(
     *             property="category_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             description="Array of category IDs for this course"
     *         )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course updated successfully",
     *         @OA\JsonContent(

     *             @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="title", type="string", example="Laravel Basics"),
     *         @OA\Property(property="description", type="string", example="A complete Laravel course"),
     *         @OA\Property(property="price", type="number", format="float", example=49.99),
     *         @OA\Property(property="sale_price", type="number", format="float", example=29.99),
     *         @OA\Property(property="price_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="price_end_date", type="string", format="date", example="2025-12-31"),

     *         @OA\Property(property="status", type="string", enum={"draft","published","archived"}, example="draft"),
     *         @OA\Property(property="status_start_date", type="string", format="date", example="2025-10-01"),
     *         @OA\Property(property="status_end_date", type="string", format="date", example="2025-12-31"),
     *         @OA\Property(property="url", type="string", example="https://example.com/laravel-basics"),
     *         @OA\Property(property="level", type="string", example="Beginner"),
     *         @OA\Property(property="cover", type="string", example=""),
     *         @OA\Property(property="preview_video_source_type", type="string", enum={"HTML","YouTube","Vimeo","External Link","Embed"}, example="YouTube"),
     *         @OA\Property(property="preview_video_url", type="string", example="https://youtu.be/example"),
     *         @OA\Property(property="preview_video_poster", type="string", example="poster.jpg"),
     *         @OA\Property(property="preview_video_embed", type="string", example="<iframe src='https://example.com/embed'></iframe>"),
     *         @OA\Property(property="duration", type="string", example="8 hours"),
     *         @OA\Property(property="video_duration", type="string", example="2 hours"),
     *         @OA\Property(property="course_preview_description", type="string", example="This is a preview of the Laravel Basics course."),
     *         @OA\Property(property="is_featured", type="boolean", example=true),
     *         @OA\Property(property="is_lock_lessons_in_order", type="boolean", example=true),
     *         @OA\Property(property="created_by", type="integer", example=1),
     *         @OA\Property(
     *             property="category_ids",
     *             type="array",
     *             @OA\Items(type="integer", example=1),
     *             description="Array of category IDs for this course"
     *         )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not have permission to perform this action.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A course with this title already exists.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="title", type="array", @OA\Items(type="string", example="The title field is required.")),
     *                 @OA\Property(property="description", type="array", @OA\Items(type="string", example="The description field is required.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="The category id field is required."))
     *             )
     *         )
     *     )
     * )
     */



    public function updatePartialCourse(CoursePartialRequest $request)
    {

        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            DB::beginTransaction();
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // FIND BY ID
            $course = Course::findOrFail($request_payload['id']);

              // SEND RESPONSE
            if (empty($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

          // ========================
            // UPDATE COURSE
            // ========================
            $request_payload['cover'] = $request_payload['cover'] ?? null;

            $course->update($request_payload);



            // ========================
            // HANDLE COVER (SINGLE FILE)
            // ========================
            $cover_filename = $course->getRawOriginal('cover');
            $folder_path = "business_1/course_{$course->id}";

            // If uploaded file
            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $new_filename = $file->hashName();
                $file->storeAs($folder_path, $new_filename, 'public');

                // Delete old cover if exists
                if ($cover_filename) {
                    $old_path = "{$folder_path}/{$cover_filename}";
                    if (Storage::disk('public')->exists($old_path)) {
                        Storage::disk('public')->delete($old_path);
                    }
                }

                $cover_filename = $new_filename;
            }

            // If string file path provided instead of upload
            if ($request->filled('cover') && is_string($request->input('cover'))) {
                $cover_filename = basename($request->input('cover'));
            }

            $course->cover = $cover_filename;
            $course->save();

            if (isset($request_payload["category_ids"])) {
                $course->categories()->sync($request_payload["category_ids"]);
            }





            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => $course
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1.0/courses/{ids}",
     *     operationId="deleteCourse",
     *     tags={"course_management.course"},
     *     summary="Delete course (role: Admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         required=true,
     *         description="Course ID (comma-separated for multiple)",
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Course deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Some data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Some data not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     )
     * )
     */

    public function deleteCourse($ids)
    {
        try {
            if (!auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer'])) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            DB::beginTransaction();

            $idsOfArray = array_map('intval', explode(',', $ids));

            // VALIDATE PAYLOAD
            $courses = Course::whereIn('id', $idsOfArray)->get();

            $existingIds = $courses->pluck('id')->toArray();

            if (count($existingIds) !== count($idsOfArray)) {
                $missingIds = array_diff($idsOfArray, $existingIds);
                return response()->json([
                    'success' => false,
                    'message' => 'Some data not found',
                    'data' => [
                        'missing_ids' => $missingIds
                    ]
                ], 400);
            }

            foreach ($courses as $course) {
                $raw_cover = $course->getRawOriginal('cover');
                if ($raw_cover) {
                    $path = "business_1/course_{$course->id}/$raw_cover";
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }


            // DELETE THE RECORDS
            Course::whereIn('id', $existingIds)->delete();







            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully',
                'data' => $existingIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
