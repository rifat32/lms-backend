<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;

class CustomWebhookController extends Controller
{
      /**
     * Handle a Stripe webhook call.
     *
     * @param  Event  $event
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleStripeWebhook(Request $request)
    {
        // Retrieve the event data from the request body
        $payload = $request->all();

        // Log the entire payload for debugging purposes

        log_message([
            'level' => 'info',
            'message' => 'Stripe Webhook received',
            'context' => ['payload' => $payload],
        ],
        'stripe_webhooks.log'
    );
      

        // Extract the event type
        $eventType = $payload['type'] ?? null;



 
        log_message([
            'level' => 'info',
            'message' => 'Event Type: ' . $eventType,
        ],
        'stripe_webhooks.log'
    );

        // Handle the event based on its type
        if ($eventType === 'checkout.session.completed') {
            // return response()->json($payload['data']['object'],409);
            $this->handleChargeSucceeded($payload['data']['object']);
        }

         // Check if it's a refund event
    if ($eventType == 'charge.refunded') {
        $event = $request->getContent();
        $eventJson = json_decode($event);
        $charge = $eventJson->data->object;

        // // Update your booking or order status based on the refund
        // $booking = Booking::where('payment_intent', $charge->payment_intent)->first();
        // if ($booking) {
        //     $booking->payment_status = 'refunded';
        //     $booking->save();

        //     JobPayment::where([
        //         "booking_id" => $booking->id,

        //     ])
        //     ->delete();
        // }

        return response()->json(['status' => 'Refund handled'], 200);
    }

        // Return a response to Stripe to acknowledge receipt of the webhook
        return response()->json(['message' => 'Webhook received']);
    }

    /**
     * Handle payment succeeded webhook from Stripe.
     *
     * @param  array  $paymentCharge
     * @return void
     */
   protected function handleChargeSucceeded($data)
{
    // Amount paid in Stripe (divide by 100 for USD)
    $amount = $data['amount_total'] ? ($data['amount_total'] / 100) : 0;

    $metadata = $data['metadata'] ?? [];

    // Ensure webhook URL matches (security check)
    if (!empty($metadata["webhook_url"]) && $metadata["webhook_url"] != route('stripe.webhook')) {
        return;
    }

    // Retrieve all payments linked to this payment_intent
    $payments = Payment::where('payment_intent_id', $data['payment_intent'])->get();

    if ($payments->isEmpty()) {
        // Fallback: handle multiple courses from metadata if payments not yet created
        $course_ids = isset($metadata['course_ids']) ? explode(',', $metadata['course_ids']) : [];
        $user_id = auth()->id() ?? null; // If user ID is stored in metadata, you can use that instead

        foreach ($course_ids as $course_id) {
            // Save Payment record if not already
            $payment = Payment::firstOrCreate([
                'payment_intent_id' => $data['payment_intent'],
                'course_id' => $course_id,
                'user_id' => $user_id,
            ], [
                'status' => 'complete',
                'amount' => $amount / count($course_ids), // approximate split
                'method' => 'stripe',
            ]);

            // Create Enrollment
            Enrollment::firstOrCreate([
                'user_id' => $user_id,
                'course_id' => $course_id,
            ], [
                'enrolled_at' => now(),
            ]);
        }
    } else {
        // Update existing payment(s) and enroll
        foreach ($payments as $payment) {
            $payment->status = 'complete';
            $payment->amount = $amount / $payments->count(); // approximate split
            $payment->save();

            Enrollment::firstOrCreate([
                'user_id' => $payment->user_id,
                'course_id' => $payment->course_id,
            ], [
                'enrolled_at' => now(),
            ]);
        }
    }
}


}
