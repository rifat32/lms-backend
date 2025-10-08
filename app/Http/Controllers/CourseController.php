<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoursePartialRequest;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
     *     path="/v1.0/client/courses",
     *     tags={"course_management.course"},
     *     operationId="getCoursesClient",
     *     summary="Get all courses",
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
     *     path="/v1.0/client/courses/{id}",
     *     tags={"course_management.course"},
     *     operationId="getCourseByIdClient",
     *     summary="Get a single course by ID",
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

    public function getCourseByIdClient($id)
    {


        $course = Course::with([
            'categories',
            'sections' => function ($q) {
                $q->with([
                    'sectionables' => function ($sq) {
                        $sq->with([
                            'sectionable' => function ($ssq) {
                                $ssq->select('id', 'title');
                            },
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
     *     tags={"course_management.course"},
     *     operationId="getCourseByIdSecureClient",
     *     summary="Get a single course by ID",
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

    public function getCourseByIdSecureClient($id)
    {
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
            ->rescrictBeforeEnrollment()


            ->find($id);



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
     * @OA\Get(
     *     path="/v1.0/client/courses/secure",
     *     tags={"course_management.course"},
     *     operationId="getCoursesClientSecure",
     *     summary="Get all courses",
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

    public function getCoursesClientSecure(Request $request)
    {
        $user_id = auth()->id();

        $query = Course::with([
            'categories' => function ($q) {
                $q->select('course_categories.id', 'course_categories.name');
            }
        ])
            ->whereHas('enrollments', function ($enrollmentQuery) {
                $enrollmentQuery->where('user_id', auth()->user()->id);
            })
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
     *     summary="Get all courses",
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

        $query = Course::with(['categories' => function ($q) {
            $q->select('course_categories.id', 'course_categories.name');
        }])->filters();

        $courses = retrieve_data($query, 'created_at', 'courses');

        // Remove pivot from all categories
        $courses['data'] = $courses['data']->each(function ($course) {
            return $course->categories->makeHidden('pivot');
        });

        // SEND RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'meta' => $courses['meta'],
            'data' => $courses['data'],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/v1.0/courses/{id}",
     *     tags={"course_management.course"},
     *     operationId="getCourseById",
     *     summary="Get a single course by ID",
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
     *     summary="Create a new course (Admin only)",
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
     *         @OA\Property(property="is_free", type="boolean", example=false),
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
     *             @OA\Property(property="is_free", type="boolean", example=false),
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
            DB::beginTransaction();
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // ADD CREATED BY
            $request_payload['created_by'] = auth()->user()->id;

            // CREATE
            $course = Course::create($request_payload);



            if ($request->hasFile('cover')) {

                $file = $request->file('cover');
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $folder_path = "business_1/course_{$course->id}";


                $file->storeAs($folder_path, $filename, 'public');

                $course->cover = $filename; // store only filename
                $course->save();
            }



            $course->categories()->sync($request_payload["category_ids"]);

            // COMMIT TRANSACTION
            DB::commit();
            // SEND RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => $course
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * @OA\Put(
     *     path="/v1.0/courses",
     *     tags={"course_management.course"},
     *     operationId="updateCourse",
     *     summary="Update a course (Admin only)",
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
     *         @OA\Property(property="is_free", type="boolean", example=false),
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
     *         @OA\Property(property="is_free", type="boolean", example=false),
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
            DB::beginTransaction();
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // FIND BY ID
            $course = Course::findOrFail($request_payload['id']);

            if ($request->hasFile('cover')) {


                $file = $request->file('cover');
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $folder_path = "business_1/course_{$course->id}";



                $file->storeAs($folder_path, $filename, 'public');

                // Delete old cover if exists
                if ($course->cover) {
                    $old_path = "business_1/course_{$course->id}/{$course->getRawOriginal('cover')}";
                    if (Storage::disk('public')->exists($old_path)) {
                        Storage::disk('public')->delete($old_path);
                    }
                }

                $course->cover = $filename; // store only filename
                $course->save();
            }
            $course->categories()->sync($request_payload["category_ids"]);

            // SEND RESPONSE
            if (empty($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // UPDATE
            $course->update($request_payload);

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
     * @OA\Patch(
     *     path="/v1.0/courses",
     *     tags={"course_management.course"},
     *     operationId="updatePartialCourse",
     *     summary="Update a course (Admin only)",
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
     *         @OA\Property(property="is_free", type="boolean", example=false),
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
     *         @OA\Property(property="is_free", type="boolean", example=false),
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
            DB::beginTransaction();
            // VALIDATE PAYLOAD
            $request_payload = $request->validated();

            // FIND BY ID
            $course = Course::findOrFail($request_payload['id']);

            if ($request->hasFile('cover')) {


                $file = $request->file('cover');
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid() . '_' . time() . '.' . $extension;
                $folder_path = "business_1/course_{$course->id}";



                $file->storeAs($folder_path, $filename, 'public');

                // Delete old cover if exists
                if ($course->cover) {
                    $old_path = "business_1/course_{$course->id}/{$course->getRawOriginal('cover')}";
                    if (Storage::disk('public')->exists($old_path)) {
                        Storage::disk('public')->delete($old_path);
                    }
                }

                $course->cover = $filename; // store only filename
                $course->save();
            }
            if (isset($request_payload["category_ids"])) {
                $course->categories()->sync($request_payload["category_ids"]);
            }

            // SEND RESPONSE
            if (empty($course)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // UPDATE
            $course->update($request_payload);

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
     *     summary="Delete course",
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
