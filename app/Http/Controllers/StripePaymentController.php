<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
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
     *     summary="Create a Stripe Payment Intent for one or more courses (role: Student only)",
     *     description="Creates a Stripe Payment Intent for selected courses. Supports optional coupon codes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_ids"},
     *             @OA\Property(property="course_ids", type="array", @OA\Items(type="integer"), example={101,102}),
     *             @OA\Property(property="coupon_code", type="string", example="SAVE20")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="clientSecret", type="string", example="pi_3OzFKa2LqWcU...secret_123"),
     *             @OA\Property(property="totalAmount", type="number", example=199.99),
     *             @OA\Property(property="discountAmount", type="number", example=20.00),
     *             @OA\Property(property="courses", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Stripe not enabled"),
     *     @OA\Response(response=404, description="Course not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */

    public function createPaymentIntent(Request $request)
    {
        try {
            DB::beginTransaction();

            // Check if user has student role
            if (!auth()->user()->hasAnyRole(['student'])) {
                return response()->json([
                    "success" => false,
                    "message" => "You cannot perform this action"
                ], 401);
            }

            // validate request
            $request_payload = $request->validate([
                'course_ids' => 'required|array|min:1',
                'coupon_code' => 'nullable|string',
            ]);


            $course_ids = $request_payload['course_ids'];
            if (empty($course_ids) || !is_array($course_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No courses selected',
                ], 400);
            }

            $courses = Course::whereIn('id', $course_ids)->get();
            if ($courses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Courses not found'
                ], 404);
            }

            // Retrieve Stripe settings
            $stripe_setting = $this->get_business_setting();
            if (empty($stripe_setting) || empty($stripe_setting->stripe_enabled)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe not enabled'
                ], 403);
            }

            $stripe = new \Stripe\StripeClient($stripe_setting->STRIPE_SECRET);

            // Ensure webhook endpoint exists (optional safety)
            try {
                $webhooks = $stripe->webhookEndpoints->all();
                $exists = collect($webhooks->data)
                    ->first(fn($endpoint) => $endpoint->url === route('stripe.webhook'));
                if (!$exists) {
                    $stripe->webhookEndpoints->create([
                        'url' => route('stripe.webhook'),
                        'enabled_events' => ['payment_intent.succeeded', 'charge.refunded'],
                    ]);
                }
            } catch (\Exception $e) {
                log_message(['level' =>  $e->getMessage()], "payment_intent.log");
            }

            // -------------------------------------
            // Calculate total and handle coupon
            // -------------------------------------
            $total_amount = $courses->sum(fn($course) => $course->computed_price + ($course->vat_amount ?? 0));

            $discount_amount = 0;
            $coupon_code = $request_payload['coupon_code'];

            if (!empty($coupon_code)) {
                $coupon = Coupon::where('code', $coupon_code)
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('coupon_start_date')
                            ->orWhereDate('coupon_start_date', '<=', now());
                    })
                    ->where(function ($query) {
                        $query->whereNull('coupon_end_date')
                            ->orWhereDate('coupon_end_date', '>=', now());
                    })
                    ->first();

                if (!$coupon) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired coupon code'
                    ], 422);
                }

                // Calculate discount based on type (flat or percent)
                if ($coupon->discount_type === Coupon::DISCOUNT_TYPE['PERCENTAGE']) {
                    $discount_amount = ($total_amount * $coupon->discount_value) / 100;
                } else {
                    $discount_amount = $coupon->discount_value;
                }

                // Prevent over-discount
                $discount_amount = min($discount_amount, $total_amount);
                $total_amount -= $discount_amount;
            }

            // -------------------------------------
            // Create PaymentIntent
            // -------------------------------------
            $metadata = [
                'course_ids' => implode(',', $courses->pluck('id')->toArray()),
                'user_id' => auth()->user()->id,
                'webhook_url' => route('stripe.webhook'),
                'coupon_code' => $coupon_code ?? 'none',
            ];

            $payment_intent = $stripe->paymentIntents->create([
                'amount' => max($total_amount, 0.50) * 100, // in cents
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $metadata,
            ]);

            // -------------------------------------
            // Save payment records
            // -------------------------------------
            foreach ($courses as $course) {
                if (!empty($course->enrollment)) {
                    throw new Exception("Course Already Enrolled", 409);
                }

                Payment::create([
                    'user_id' => auth()->user()->id,
                    'course_id' => $course->id,
                    'amount' => $course->computed_price,
                    'method' => 'stripe',
                    'status' => 'pending',
                    'payment_intent_id' => $payment_intent->id,
                    'coupon_code' => $coupon_code,
                    'discount_amount' => $discount_amount / count($courses),
                ]);

                $course->update([
                    'payment_status' => 'pending',
                    'payment_method' => 'stripe',
                ]);
            }

            DB::commit();

            return response()->json([
                'clientSecret' => $payment_intent->client_secret,
                'totalAmount' => round($total_amount, 2),
                'discountAmount' => round($discount_amount, 2),
                'courses' => $courses->pluck('title'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-13T12:00:00Z"),
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
        ])
            ->where([
                "status" => "completed"
            ]);

        $this->applyFilters($query, $request);

        $payments = retrieve_data($query, 'created_at', 'payments', 'desc');
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
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    /**
     * Get payment summary statistics
     */
    private function getPaymentSummary()
    {
        $now = now();

        $total_earnings = (float) Payment::where('status', 'completed')->sum('amount');

        $this_month_earnings = (float) Payment::where('status', 'completed')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->sum('amount');

        $this_week_earnings = (float) Payment::where('status', 'completed')
            ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->sum('amount');

        $today_earnings = (float) Payment::where('status', 'completed')
            ->whereDate('created_at', $now->copy()->toDateString())
            ->sum('amount');

        return [
            'total_earnings' => $total_earnings,
            'this_month_earnings' => $this_month_earnings,
            'this_week_earnings' => $this_week_earnings,
            'today_earnings' => $today_earnings,
            'available_balance' => $total_earnings
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
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-13T12:00:00Z"),
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
