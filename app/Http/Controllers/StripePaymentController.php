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
use Illuminate\Support\Facades\DB;

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
     *     summary="Create a Stripe Payment Intent for a course (role: Student only)",
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

        try{
  DB::beginTransaction();

        if (!auth()->user()->hasAnyRole(['student'])) {
    return response()->json([
        "message" => "You can not perform this action"
    ], 401);
}

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

            if(!empty($course->enrollment)) {
              throw new Exception("Course Already Enrolled",409);
            }

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
        } catch(Exception $e) {

           return  response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode());

        }

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


     /**
     * @OA\Get(
     *     path="/v1.0/payments",
     *     tags={"Payments"},
     *     operationId="getPayments",
     *     summary="Get payments with filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="course_id",
     *         in="query",
     *         required=false,
     *         description="Filter by course ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by payment status: completed, pending, failed",
     *         @OA\Schema(type="string", example="completed")
     *     ),
     *     @OA\Parameter(
     *         name="transaction_id",
     *         in="query",
     *         required=false,
     *         description="Search by transaction ID",
     *         @OA\Schema(type="string", example="PAY-001")
     *     ),
     *     @OA\Parameter(
     *         name="student_name",
     *         in="query",
     *         required=false,
     *         description="Search by student name",
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Filter from date (Y-m-d)",
     *         @OA\Schema(type="string", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="Filter to date (Y-m-d)",
     *         @OA\Schema(type="string", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of payments",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payments retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="transaction_id", type="string", example="PAY-001"),
     *                     @OA\Property(property="amount", type="number", format="float", example=73.99),
     *                     @OA\Property(property="method", type="string", example="credit_card"),
     *                     @OA\Property(property="status", type="string", example="completed"),
     *                     @OA\Property(property="paid_at", type="string", format="date-time", example="2024-01-13T12:00:00Z"),
     *                     @OA\Property(
     *                         property="course",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="React Fundamentals")
     *                     ),
     *                     @OA\Property(
     *                         property="student",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="total_earnings", type="number", format="float", example=15420.5),
     *                 @OA\Property(property="this_month_earnings", type="number", format="float", example=3240.75),
     *                 @OA\Property(property="available_balance", type="number", format="float", example=892.3)
     *             )
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
    public function getPayments(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['owner', 'admin', 'lecturer'])) {
            return response()->json([
                "success" => false,
                "message" => "You do not have permission to access this resource"
            ], 403);
        }

        $query = Payment::with([
            'course:id,title',
            'student:id,first_name,last_name,email'
        ]);

        $this->applyFilters($query, $request);

        $payments = retrieve_data($query, 'paid_at', 'payments', 'desc');
        $summary = $this->getPaymentSummary();

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully',
            'data' => $payments['data'],
            'meta' => $payments['meta'],
            'summary' => $summary
        ], 200);
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', 'like', '%' . $request->transaction_id . '%');
        }

        if ($request->filled('student_name')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->student_name . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }
    }

    /**
     * Get payment summary statistics
     */
    private function getPaymentSummary()
    {
        $totalEarnings = (float) Payment::where('status', 'completed')->sum('amount');

        $thisMonthEarnings = (float) Payment::where('status', 'completed')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');

        return [
            'total_earnings' => $totalEarnings,
            'this_month_earnings' => $thisMonthEarnings,
            'available_balance' => $totalEarnings
        ];
    }

    /**
     * @OA\Get(
     *     path="/v1.0/payments/{id}",
     *     tags={"Payments"},
     *     operationId="getPaymentDetail",
     *     summary="Get payment details by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="transaction_id", type="string", example="PAY-001"),
     *                 @OA\Property(property="amount", type="number", format="float", example=73.99),
     *                 @OA\Property(property="method", type="string", example="credit_card"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="payment_intent_id", type="string", example="pi_123456789"),
     *                 @OA\Property(property="paid_at", type="string", format="date-time", example="2024-01-13T12:00:00Z"),
     *                 @OA\Property(
     *                     property="course",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="React Fundamentals"),
     *                     @OA\Property(property="description", type="string", example="Learn React fundamentals")
     *                 ),
     *                 @OA\Property(
     *                     property="student",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment not found")
     *         )
     *     )
     * )
     */
    public function getPaymentDetail($id)
    {
        $payment = Payment::with([
            'course:id,title,description',
            'student:id,first_name,last_name,email'
        ])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully',
            'data' => $payment
        ], 200);
    }
}
