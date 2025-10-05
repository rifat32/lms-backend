<?php

namespace App\Utils;

use App\Models\BusinessSetting;

trait BasicUtil
{


 public function get_business_setting()
    {
        return BusinessSetting::first();
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
