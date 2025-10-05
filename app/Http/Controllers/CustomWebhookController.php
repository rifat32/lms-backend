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
        Log::info('Webhook Payload: ' . json_encode($payload));

        // Extract the event type
        $eventType = $payload['type'] ?? null;

        // Log the event type
        Log::info('Event Type: ' . $eventType);

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

        // Extract required data from payment charge
        $amount = $data['amount_total'] ? ($data['amount_total'] / 100) :  0;

        $metadata = $data["metadata"] ?? [];
        // Add more fields as needed

        if(!empty($metadata["webhook_url"]) && $metadata["webhook_url"] != route('stripe.webhook')){
               return;
        }


        $payment = Payment::where('payment_intent_id', $data['payment_intent'])->first();
        $payment->status = 'complete';
        $payment->amount = $amount;
        $payment->save();


        Enrollment::create([
            'user_id' => $payment->user_id,
            'course_id' => $payment->course_id,
            'enrolled_at' => now(),
        ]);

    }


}
