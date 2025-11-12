<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Utils\BasicUtil;
use Exception;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\WebhookEndpoint;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

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

            // Check if user is already enrolled in any of the courses
            $user_id = auth()->id();
            $enrolled_course_ids = Enrollment::whereIn('course_id', $course_ids)
                ->where('user_id', $user_id)
                ->pluck('course_id')
                ->toArray();

            if (!empty($enrolled_course_ids)) {
                $enrolled_titles = $courses->whereIn('id', $enrolled_course_ids)->pluck('title')->toArray();
                return response()->json([
                    'success' => false,
                    'message' => 'You are already enrolled in: ' . implode(', ', $enrolled_titles)
                ], 409);
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
            $coupon_code = $request_payload['coupon_code'] ?? null;

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
                    $discount_amount = ($total_amount * $coupon->discount_amount) / 100;
                } else {
                    $discount_amount = $coupon->discount_amount;
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
                'amount' => max($total_amount, 0.50) * 100, // in pence for GBP
                'currency' => 'gbp',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $metadata,
            ]);

            // -------------------------------------
            // Save payment records
            // -------------------------------------
            foreach ($courses as $course) {
                Payment::create([
                    'user_id' => auth()->id(),
                    'course_id' => $course->id,
                    'amount' => $course->computed_price,
                    'method' => 'stripe',
                    'status' => 'pending',
                    'payment_intent_id' => $payment_intent->id,
                    'coupon_code' => $coupon_code,
                    'discount_amount' => $discount_amount / count($courses),
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
        ])
            ->where([
                "status" => "completed"
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
        $now = now();

        $total_earnings = (float) Payment::where('status', 'completed')->sum('amount');

        $this_month_earnings = (float) Payment::where('status', 'completed')
            ->whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->sum('amount');

        $this_week_earnings = (float) Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->sum('amount');

        $today_earnings = (float) Payment::where('status', 'completed')
            ->whereDate('paid_at', $now->copy()->toDateString())
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

    /**
     * @OA\Get(
     *     path="/v1.0/payments/{paymentId}/pay-slip",
     *     tags={"Payments"},
     *     operationId="downloadPaymentSlip",
     *     summary="Download payment slip PDF",
     *     description="Download a PDF payment slip for a specific payment transaction",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paymentId",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="string", example="PAY-001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function downloadPaymentSlip(string $paymentId)
    {
        /**
         * Supported Stripe Payment Methods:
         * - card: Credit/Debit cards (Visa, Mastercard, Amex, etc.)
         * - Google Pay (via card with wallet type)
         * - Apple Pay (via card with wallet type)
         * - bank_transfer: Bank transfers
         * - us_bank_account: US Bank Account (ACH)
         * - sepa_debit: SEPA Direct Debit
         * - bacs_debit: Bacs Direct Debit (UK)
         * - paypal: PayPal
         * - klarna: Klarna
         * - afterpay_clearpay: Afterpay/Clearpay
         * - alipay: Alipay
         * - wechat_pay: WeChat Pay
         * - amazon_pay: Amazon Pay
         * - cashapp: Cash App Pay
         * - link: Stripe Link
         */

        $paymentModel = Payment::with([
            'course:id,title,description,price',
            'student:id,first_name,last_name,email,phone',
        ])->findOrFail($paymentId);

        $business = optional(auth()->user())->business;

        // Organization
        $org = [
            'name'    => $business->name ?? 'Your Organization',
            'logo'    => $business->logo ?? null,
            'address' => $business->address_line_1 ?? '',
            'phone'   => $business->phone ?? '',
            'email'   => $business->email ?? '',
            'website' => $business->website ?? '',
        ];

        // Student
        $studentArr = [
            'name'  => trim(($paymentModel->student->first_name ?? '') . ' ' . ($paymentModel->student->last_name ?? '')) ?: 'Student',
            'email' => $paymentModel->student->email ?? '',
            'phone' => $paymentModel->student->phone ?? 'N/A',
        ];

        // Initialize Stripe data structure
        $stripeData = [
            'transaction_id'      => $paymentModel->transaction_id ?? 'N/A',
            'payment_intent'      => $paymentModel->payment_intent_id ?? 'N/A',
            'charge_id'           => 'N/A',
            'payment_method_type' => 'N/A',
            'currency'            => 'GBP',
            'receipt_url'         => null,

            // Card specific
            'card_brand'          => null,
            'card_last4'          => null,
            'card_exp_month'      => null,
            'card_exp_year'       => null,
            'card_funding'        => null, // credit, debit, prepaid

            // Bank transfer specific
            'bank_name'           => null,
            'bank_account_last4'  => null,

            // Digital wallet specific (Google Pay, Apple Pay, etc.)
            'wallet_type'         => null, // google_pay, apple_pay, etc.

            // Other payment methods
            'payment_details'     => null, // Generic field for other payment types
        ];

        $discountAmount = 0;
        $actualAmount = $paymentModel->amount;

        if (!empty($paymentModel->payment_intent_id)) {
            try {
                // Get Stripe settings
                $stripe_setting = $this->get_business_setting();
                if (!empty($stripe_setting) && !empty($stripe_setting->STRIPE_SECRET)) {
                    $stripe = new \Stripe\StripeClient($stripe_setting->STRIPE_SECRET);

                    // Retrieve PaymentIntent details
                    $paymentIntent = $stripe->paymentIntents->retrieve($paymentModel->payment_intent_id);

                    if ($paymentIntent) {
                        // Get currency
                        $stripeData['currency'] = strtoupper($paymentIntent->currency ?? 'GBP');

                        // Get actual amount from Stripe (convert from cents/smallest unit)
                        $actualAmount = ($paymentIntent->amount ?? 0) / 100;

                        // Get discount/coupon info from metadata or database
                        if (!empty($paymentModel->discount_amount)) {
                            $discountAmount = $paymentModel->discount_amount;
                        } else {
                            $metadata = $paymentIntent->metadata ?? [];
                            if (!empty($metadata['coupon_code']) && $metadata['coupon_code'] !== 'none') {
                                $originalAmount = $paymentModel->amount;
                                $discountAmount = max(0, $originalAmount - $actualAmount);
                            }
                        }

                        // Get latest charge for more details
                        if (!empty($paymentIntent->latest_charge)) {
                            $charge = $stripe->charges->retrieve($paymentIntent->latest_charge);

                            if ($charge) {
                                $stripeData['charge_id'] = $charge->id ?? 'N/A';
                                $stripeData['receipt_url'] = $charge->receipt_url ?? null;

                                // Payment method details - handle different types
                                if (!empty($charge->payment_method_details)) {
                                    $pmDetails = $charge->payment_method_details;
                                    $pmType = $pmDetails->type ?? 'unknown';
                                    $stripeData['payment_method_type'] = Str::title(str_replace('_', ' ', $pmType));

                                    switch ($pmType) {
                                        case 'card':
                                            // Credit/Debit Card
                                            if (!empty($pmDetails->card)) {
                                                $card = $pmDetails->card;
                                                $stripeData['card_brand'] = Str::title($card->brand ?? 'N/A');
                                                $stripeData['card_last4'] = $card->last4 ?? 'N/A';
                                                $stripeData['card_exp_month'] = $card->exp_month ?? null;
                                                $stripeData['card_exp_year'] = $card->exp_year ?? null;
                                                $stripeData['card_funding'] = Str::title($card->funding ?? null); // credit, debit, prepaid

                                                // Check if it's a wallet transaction (Google Pay, Apple Pay via card)
                                                if (!empty($card->wallet)) {
                                                    $stripeData['wallet_type'] = Str::title(str_replace('_', ' ', $card->wallet->type ?? ''));
                                                }
                                            }
                                            break;

                                        case 'bank_transfer':
                                        case 'us_bank_account':
                                        case 'sepa_debit':
                                        case 'bacs_debit':
                                            // Bank Transfer / Direct Debit
                                            $bankData = $pmDetails->$pmType ?? null;
                                            if ($bankData) {
                                                $stripeData['bank_name'] = $bankData->bank_name ?? 'N/A';
                                                $stripeData['bank_account_last4'] = $bankData->last4 ?? 'N/A';
                                            }
                                            break;

                                        case 'paypal':
                                            // PayPal
                                            if (!empty($pmDetails->paypal)) {
                                                $stripeData['payment_details'] = 'PayPal: ' . ($pmDetails->paypal->payer_email ?? 'N/A');
                                            }
                                            break;

                                        case 'klarna':
                                            // Klarna
                                            $stripeData['payment_details'] = 'Klarna Pay Later';
                                            break;

                                        case 'afterpay_clearpay':
                                            // Afterpay/Clearpay
                                            $stripeData['payment_details'] = 'Afterpay/Clearpay';
                                            break;

                                        case 'alipay':
                                            // Alipay
                                            if (!empty($pmDetails->alipay)) {
                                                $stripeData['payment_details'] = 'Alipay';
                                            }
                                            break;

                                        case 'wechat_pay':
                                            // WeChat Pay
                                            $stripeData['payment_details'] = 'WeChat Pay';
                                            break;

                                        case 'amazon_pay':
                                            // Amazon Pay
                                            $stripeData['payment_details'] = 'Amazon Pay';
                                            break;

                                        case 'link':
                                            // Stripe Link
                                            if (!empty($pmDetails->link)) {
                                                $stripeData['payment_details'] = 'Link (Stripe)';
                                            }
                                            break;

                                        case 'cashapp':
                                            // Cash App Pay
                                            if (!empty($pmDetails->cashapp)) {
                                                $stripeData['payment_details'] = 'Cash App Pay: ' . ($pmDetails->cashapp->cashtag ?? 'N/A');
                                            }
                                            break;

                                        default:
                                            // Generic handling for other payment types
                                            $stripeData['payment_details'] = 'Payment via ' . Str::title(str_replace('_', ' ', $pmType));
                                            break;
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue with default values
                Log::error('Failed to fetch Stripe payment details: ' . $e->getMessage());
            }
        }

        // Payment meta
        $payMeta = [
            'slip_no'        => now()->format('Y') . '-' . str_pad($paymentModel->id, 5, '0', STR_PAD_LEFT),
            'date'           => $paymentModel->paid_at ?? $paymentModel->created_at ?? now(),
            'status'         => $paymentModel->status === 'completed' ? 'Paid' : ($paymentModel->status === 'pending' ? 'Pending' : 'Failed'),
            'currency'       => $stripeData['currency'],
            'notes'          => 'Thank you for your payment. Keep a copy for your records.',
            'subtotal'       => $paymentModel->amount,
            'discount_amount' => $discountAmount,
            'processing_fee' => 0,
            'amount_paid'    => $actualAmount,
        ];

        // Courses
        $courseItems = [];
        $courseRel = $paymentModel->course;
        if ($courseRel) {
            $courseItems[] = [
                'title' => $courseRel->title ?? 'N/A',
                'description' => $courseRel->description ?? null,
                'start_date' => null,
                'end_date' => null,
                'qty' => 1,
                'unit_price' => (float)($courseRel->price ?? $paymentModel->amount),
                'line_discount' => $discountAmount,
            ];
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.payment-slip', [
            'org'     => $org,
            'student' => $studentArr,
            'payment' => $payMeta,
            'stripe'  => $stripeData,
            'courses' => $courseItems,
        ])
            ->setPaper('A4');

        return $pdf->download(($payMeta['slip_no'] ?? "payment-slip") . '.pdf');
    }
}
