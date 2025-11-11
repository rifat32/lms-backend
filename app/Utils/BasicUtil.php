<?php

namespace App\Utils;

use App\Models\BusinessSetting;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Notification;
use App\Models\QuizAttempt;
use App\Models\Section;
use Exception;
use App\Mail\CourseCompletedMail;
use App\Models\Course;
use Illuminate\Support\Facades\Mail;

trait BasicUtil
{


    public function get_business_setting()
    {
        return BusinessSetting::first();
    }

    public function recalculateCourseProgress($course_id)
    {
        $sections = Section::where('course_id', $course_id)
            ->with('sectionables.sectionable')
            ->get();

        $lesson_ids = [];
        $quiz_ids = [];

        foreach ($sections as $section) {
            foreach ($section->sectionables as $sectionable) {
                $model = $sectionable->sectionable;
                if ($model instanceof \App\Models\Lesson) {
                    $lesson_ids[] = $model->id;
                } elseif ($model instanceof \App\Models\Quiz) {
                    $quiz_ids[] = $model->id;
                }
            }
        }

        $total_items = count($lesson_ids) + count($quiz_ids);

        // ✅ Count completed lessons (distinct)
        $completed_lessons = LessonProgress::where('user_id', auth()->user()->id)
            ->whereIn('lesson_id', $lesson_ids)
            ->where('course_id', $course_id)
            ->where('is_completed', 1)
            ->distinct('lesson_id')
            ->count('lesson_id');

        // ✅ Count completed quizzes (distinct)
        $completed_quizzes = QuizAttempt::where('user_id', auth()->user()->id)
            ->where("course_id", $course_id)
            ->whereIn('quiz_id', $quiz_ids)
            ->where('is_passed', 1)
            ->whereNotNull('completed_at')
            ->where('is_expired', 0)
            ->distinct('quiz_id')
            ->count('quiz_id');

        $completed_items = $completed_lessons + $completed_quizzes;

        $percentage = $total_items > 0 ? round(($completed_items / $total_items) * 100, 2) : 0;

        // ✅ Update enrollment progress
        Enrollment::where('user_id', auth()->user()->id)
            ->where('course_id', $course_id)
            ->update(['progress' => $percentage]);


        // ✅ Send email when reaching 100% (but only once)
        if ($percentage == 100) {
            $user = auth()->user();
            $course = Course::find($course_id);

            if ($user && $course) {
                // Send course completion email to student
                try {
                    Mail::to($user->email)->send(new CourseCompletedMail($user, $course));

                    // Create notification for student
                    Notification::create([
                        'type' => 'App\\Notifications\\CourseCompleted',
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id' => $user->id,
                        'data' => json_encode([
                            'course_id' => $course->id,
                            'course_name' => $course->name,
                            'completed_at' => now()->toDateTimeString(),
                            'progress' => $percentage,
                        ]),
                        'entity_id' => $course->id,
                        'entity_name' => 'course',
                        'notification_title' => 'Course Completed!',
                        'notification_description' => "Congratulations! You have completed {$course->name}",
                        'notification_link' => "/dashboard/courses/{$course->id}/certificate",
                        'sender_id' => $user->business_id ? $user->business->owner->id : $user->id,
                        'receiver_id' => $user->id,
                        'business_id' => $user->business_id,
                        'is_system_generated' => true,
                        'notification_type' => 'course_completed',
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't break the flow
                }

                // Send notification to business owner if applicable
                if ($user->business_id) {
                    $business = $user->business()->with('owner')->first();

                    if ($business && $business->owner && $business->owner->email) {
                        try {
                            // Create notification for business owner
                            Notification::create([
                                'type' => 'App\\Notifications\\StudentCourseCompleted',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id' => $business->owner->id,
                                'data' => json_encode([
                                    'student_id' => $user->id,
                                    'student_name' => $user->name,
                                    'course_id' => $course->id,
                                    'course_name' => $course->name,
                                    'completed_at' => now()->toDateTimeString(),
                                ]),
                                'entity_id' => $course->id,
                                'entity_name' => 'course',
                                'notification_title' => 'Student Completed Course',
                                'notification_description' => "{$user->name} has completed {$course->name}",
                                'notification_link' => "/dashboard/students/{$user->id}/progress",
                                'sender_id' => $user->id,
                                'receiver_id' => $business->owner->id,
                                'business_id' => $business->id,
                                'is_system_generated' => true,
                                'notification_type' => 'student_course_completed',
                            ]);
                        } catch (\Exception $e) {
                            // Log error but don't break the flow
                        }
                    }
                }
            }
        }

        return [
            $percentage,
            [
                "lesson_ids" => $lesson_ids,
                "completed_lessons" => $completed_lessons
            ],
            [
                "quiz_ids" => $quiz_ids,

                "completed_quizzes" =>  $completed_quizzes
            ]

        ];
    }



    //     public function processRefund($booking)
    // {

    //     $stripeSetting = $this->get_business_setting($booking->garage_id);

    //     if (empty($stripeSetting)) {
    //         throw new Exception("No stripe seting found", 403);
    //     }

    //     if (empty($stripeSetting->stripe_enabled)) {
    //         throw new Exception("Stripe is not enabled", 403);
    //     }
    //     // Set Stripe API key
    //     $stripe = new \Stripe\StripeClient($stripeSetting->STRIPE_SECRET);

    //     // Find the payment intent or charge for the booking
    //     $paymentIntent = $booking->payment_intent_id;

    //     if (empty($paymentIntent)) {
    //         return response()->json([
    //             "message" => "No payment record found for this booking."
    //         ], 404);
    //     }

    //     // Create a refund for the payment intent
    //     try {
    //         $refund = $stripe->refunds->create([
    //             'payment_intent' => $paymentIntent, // Reference the payment intent
    //             'amount' => $booking->final_price * 100, // Refund full amount in cents
    //         ]);

    //         $booking->payment_status = 'refunded';
    //         $booking->save();
    //         JobPayment::where([
    //             "booking_id" => $booking->id,
    //         ])
    //             ->delete();
    //         return response()->json([
    //             "message" => "Refund successful",
    //             "refund_id" => $refund->id
    //         ], 200);
    //     } catch (Exception $e) {
    //         throw new Exception("Error processing refund: " . $e->getMessage(), 500);
    //     }
    // }


}
