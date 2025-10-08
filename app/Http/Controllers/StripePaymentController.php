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
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

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
        // Retrieve multiple courses
        $course_ids = $request->course_ids; // array of IDs
        if (empty($course_ids) || !is_array($course_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No courses selected',
                'error' => 'No courses selected'
            ], 400);
        }

        $courses = Course::whereIn('id', $course_ids)->get();
        if ($courses->isEmpty()) {
            return response()->json(['error' => 'Courses not found'], 404);
        }

        // Stripe settings
        $stripe_setting = $this->get_business_setting();
        if (empty($stripe_setting) || empty($stripe_setting->stripe_enabled)) {
            return response()->json(['error' => 'Stripe not enabled'], 403);
        }

        $stripe = new \Stripe\StripeClient($stripe_setting->STRIPE_SECRET);

        // -------------------------------
        // Ensure webhook endpoint exists
        // -------------------------------
        try {
            $webhookEndpoints = $stripe->webhookEndpoints->all();
            $existingEndpoint = collect($webhookEndpoints->data)
                ->first(fn($endpoint) => $endpoint->url === route('stripe.webhook'));

            if (!$existingEndpoint) {
                $stripe->webhookEndpoints->create([
                    'url' => route('stripe.webhook'),
                    'enabled_events' => ['payment_intent.succeeded', 'charge.refunded'],
                ]);
            }
        } catch (\Exception $e) {
            log_message([
                'level' =>  $e->getMessage(),
            ], "hook.txt");
        }

        // -------------------------------
        // Calculate total amount
        // -------------------------------
        $total_amount = $courses->sum(fn($course) => $course->sale_price + ($course->vat_amount ?? 0));

        // Prepare metadata with all course IDs and webhook URL
        $metadata = [
            'course_ids' => implode(',', $courses->pluck('id')->toArray()),
            'webhook_url' => route('stripe.webhook'),
            'user_id' => auth()->user()->id,
        ];

        // -------------------------------
        // Create PaymentIntent
        // -------------------------------
        $payment_intent = $stripe->paymentIntents->create([
            'amount' => $total_amount * 100, // Stripe uses smallest currency unit
            'currency' => 'usd',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => $metadata,
        ]);

        // -------------------------------
        // Save payment for each course
        // -------------------------------
        foreach ($courses as $course) {
            Payment::create([
                'user_id' => auth()->user()->id,
                'course_id' => $course->id,
                'amount' => $course->sale_price,
                'method' => 'stripe',
                'status' => 'pending',
                'payment_intent_id' => $payment_intent->id,
            ]);

            $course->update([
                'payment_status' => 'pending',
                'payment_method' => 'stripe',
            ]);
        }

        // -------------------------------
        // Return client secret and info
        // -------------------------------
        return response()->json([
            'clientSecret' => $payment_intent->client_secret,
            'totalAmount' => $total_amount,
            'courses' => $courses->pluck('title'),
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
