<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;

class CustomWebhookController extends Controller
{
    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->all();

        log_message([
            'level' => 'info',
            'message' => 'Stripe Webhook received',
            'context' => ['payload' => $payload],
        ], 'stripe_webhooks.log');

        // If it's a Stripe test webhook, type may be missing in test payloads.
        $event_type = $payload['type'] ?? 'payment_intent.succeeded';

        log_message([
            'level' => 'info',
            'message' => 'Event Type: ' . $event_type,
        ], 'stripe_webhooks.log');

        // Handle payment success
        if ($event_type === 'payment_intent.succeeded') {
            // The payload might already contain the object itself
            $data = isset($payload['data']['object']) ? $payload['data']['object'] : ($payload['object'] ?? []);
            $this->handleChargeSucceeded($data);
        }

        // Handle refunds
        if ($event_type === 'charge.refunded') {
            return response()->json(['status' => 'Refund handled'], 200);
        }

        return response()->json(['message' => 'Webhook received']);
    }

    protected function handleChargeSucceeded($data)
    {
        // Stripe sends amount in cents (4999 → $49.99)
        $amount = isset($data['amount']) ? $data['amount'] / 100 : 0;

        $metadata = $data['metadata'] ?? [];

        // Optional security: verify webhook_url
        if (!empty($metadata["webhook_url"]) && $metadata["webhook_url"] != route('stripe.webhook')) {
            log_message([
                'level' => 'warning',
                'message' => 'Webhook URL mismatch',
                'context' => $metadata,
            ], 'stripe_webhooks.log');
            return;
        }

        $user_id = $metadata['user_id'] ?? null;
        $course_ids = isset($metadata['course_ids']) ? explode(',', $metadata['course_ids']) : [];

        if (!$user_id || empty($course_ids)) {
            log_message([
                'level' => 'error',
                'message' => 'Missing user_id or course_ids in Stripe metadata',
                'context' => $metadata,
            ], 'stripe_webhooks.log');
            return;
        }

        $payment_intent_id = $data['id'] ?? $data['payment_intent'] ?? null;

         $transaction_id = $data['id'] ?? $data['id'] ?? null;

        foreach ($course_ids as $course_id) {
          $payment = Payment::updateOrCreate(
    [
        'payment_intent_id' => $payment_intent_id,
        'course_id' => $course_id,
        'user_id' => $user_id,
    ],
    [
        'status' => 'complete',
        'amount' => $amount / count($course_ids),
        'method' => 'stripe',
        "transaction_id" => $transaction_id
    ]
);

            // Enroll user
            Enrollment::firstOrCreate(
                [
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                ],
                [
                    'enrolled_at' => now(),
                ]
            );
        }

        log_message([
            'level' => 'info',
            'message' => 'Payment processed successfully',
            'context' => ['user_id' => $user_id, 'course_ids' => $course_ids],
        ], 'stripe_webhooks.log');
    }
}
