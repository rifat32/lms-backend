<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Utils\BasicUtil;
use Exception;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Endpoints for handling course payments via Stripe"
 * )
 */
class StripePaymentController extends Controller
{
    use BasicUtil;

    /**
     * @OA\Post(
     *     path="/v1.0/payments/intent",
     *     operationId="createPaymentIntent",
     *     tags={"Payments"},
     *     summary="Create a Stripe Payment Intent for a course",
     *     description="Creates a Stripe Payment Intent for the authenticated user to pay for a course. Discount and coupon logic are currently disabled.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id"},
     *             @OA\Property(property="course_id", type="integer", example=101, description="The ID of the course being purchased")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="clientSecret", type="string", example="pi_3OzFKa2LqWcU...secret_123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid request payload.")
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
     *         description="Forbidden - Stripe not enabled or settings missing",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Stripe is not enabled.")
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
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The course_id field is required."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="course_id", type="array",
     *                     @OA\Items(type="string", example="The course_id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while creating the payment intent.")
     *         )
     *     )
     * )
     */
    public function createPaymentIntent(Request $request)
    {
        // Retrieve course
        $course_id = $request->course_id;
        $course = Course::findOrFail($course_id);

        // Stripe settings retrieval based on business or garage ID
        $stripe_setting = $this->get_business_setting();

        if (empty($stripe_setting)) {
            throw new Exception("No stripe setting found", 403);
        }

        if (empty($stripe_setting->stripe_enabled)) {
            throw new Exception("Stripe is not enabled", 403);
        }

        // Initialize Stripe client
        $stripe = new \Stripe\StripeClient($stripe_setting->STRIPE_SECRET);

        // -------------------------------
        // Commented out discount/coupon logic
        // -------------------------------
        // $discount = $this->canculate_discount_amount($course->price, $course->discount_type, $course->discount_amount);
        // $coupon_discount = $this->canculate_discount_amount($course->price, $course->coupon_discount_type, $course->coupon_discount_amount);
        // $total_discount = $discount + $coupon_discount;

        // -------------------------------
        // Commented out tip logic (if not used)
        // -------------------------------
        // $totalTip = $this->canculate_discount_amount(
        //     $course->price,
        //     $course->tip_type,
        //     $course->tip_amount
        // );

        // Prepare payment intent data
        $payment_intent_data = [
            'amount' => ($course->sale_price + ($course->vat_amount ?? 0)) * 100, // Stripe uses smallest currency unit (e.g., cents)
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'metadata' => [
                'course_id' => $course->id,
                'webhook_url' => route('stripe.webhook'),
            ],
        ];

        // -------------------------------
        // Commented out discount handling
        // -------------------------------
        // if ($total_discount > 0) {
        //     $coupon = $stripe->coupons->create([
        //         'amount_off' => $total_discount * 100,
        //         'currency' => 'usd',
        //         'duration' => 'once',
        //         'name' => 'Discount',
        //     ]);
        //
        //     $payment_intent_data['discounts'] = [
        //         [
        //             'coupon' => $coupon->id,
        //         ],
        //     ];
        // }

        // Create Stripe payment intent
        $payment_intent = $stripe->paymentIntents->create($payment_intent_data);

        // Save to CoursePayment model
        Payment::create([
            'user_id' => auth()->user()->id ?? 1,
            'course_id' => $course->id,
            'amount' => $course->sale_price,
            'method' => 'stripe',
            'status' => 'pending',
            'payment_intent_id' => $payment_intent->id,

        ]);

        // Optionally update course or related booking
        $course->update([
            'payment_status' => 'pending',
            'payment_method' => 'stripe',
        ]);

        return response()->json([
            'clientSecret' => $payment_intent->client_secret
        ]);
    }

    public function createRefund(Request $request)
    {
        $bookingId = $request->booking_id;
        $booking = Booking::findOrFail($bookingId);

        // Get the Stripe settings
        $stripeSetting = $this->get_business_setting($booking->garage_id);


        if (empty($stripeSetting)) {
            throw new Exception("No stripe seting found", 403);
        }

        if (empty($stripeSetting->stripe_enabled)) {
            throw new Exception("Stripe is not enabled", 403);
        }
        // Set Stripe API key
        $stripe = new \Stripe\StripeClient($stripeSetting->STRIPE_SECRET);

        // Find the payment intent or charge for the booking
        $paymentIntent = $booking->payment_intent_id;

        if (empty($paymentIntent)) {
            return response()->json([
                "message" => "No payment record found for this booking."
            ], 404);
        }

        // Create a refund for the payment intent
        try {
            $refund = $stripe->refunds->create([
                'payment_intent' => $paymentIntent, // Reference the payment intent
                'amount' => $booking->final_price * 100, // Refund full amount in cents
            ]);

            // Update the booking or any other record to reflect the refund
            $booking->payment_status = 'refunded';
            $booking->save();
            JobPayment::where([
                "booking_id" => $booking->id
            ])
                ->delete();
            return response()->json([
                "message" => "Refund successful",
                "refund_id" => $refund->id
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "message" => "Refund failed: " . $e->getMessage()
            ], 500);
        }
    }
}
