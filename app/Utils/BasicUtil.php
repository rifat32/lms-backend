<?php

namespace App\Utils;

use App\Models\BusinessSetting;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\Section;

trait BasicUtil
{


 public function get_business_setting()
    {
        return BusinessSetting::first();
    }

    public function recalculateCourseProgress($user_id, $course_id)
{
    // ✅ Collect all lessons and quizzes of the course
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

    // ✅ Count completed lessons
    $completed_lessons = LessonProgress::where('user_id', $user_id)
        ->whereIn('lesson_id', $lesson_ids)
        ->where('is_completed', true)
        ->count();

    // ✅ Count completed quizzes
    $completed_quizzes = QuizAttempt::where('user_id', $user_id)
        ->where("is_passed", 1)
        ->whereIn('quiz_id', $quiz_ids)
        ->whereNotNull('completed_at')
        ->where('is_expired', false)
        ->count();

    $completed_items = $completed_lessons + $completed_quizzes;

    $percentage = $total_items > 0 ? round(($completed_items / $total_items) * 100, 2) : 0;

    // ✅ Update enrollment progress
    Enrollment::where('user_id', $user_id)
        ->where('course_id', $course_id)
        ->update(['progress' => $percentage]);

    return $percentage;
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
