<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use App\Models\SubUser;
use App\Models\Plans;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StripeEventListener
{
    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event)
    {
        Log::info('Stripe Webhook Received: ' . $event->payload['type']);
        
        if ($event->payload['type'] === 'invoice.payment_succeeded') {
            $invoice = $event->payload['data']['object'];
            $user = User::where('stripe_id', $invoice['customer'])->first();

            if ($user) {
                // Check if the invoice is for the specific plan (Price ID from env)
                // In a real app, you'd loop through lines to find the plan
                $envPriceId = env('STRIPE_PRICE_ID');
                $isCorrectPlan = false;
                foreach ($invoice['lines']['data'] as $line) {
                    if ($line['price']['id'] === $envPriceId) {
                        $isCorrectPlan = true;
                        break;
                    }
                }

                if ($isCorrectPlan) {
                    // Update/Create SubUser record
                    // Assuming we have a Plan in DB that matches this.
                    // If not, we might need to hardcode specific values or fetch from Plans table where price matches.
                    // Since Plans model doesn't have stripe_price_id yet, we'll try to find a plan or use defaults.

                    // Logic: Get the Plan details to know how many sessions to give
                    // For now, let's assume valid plan with ID 1 or logic based on user request
                    // The user said "ak product banya ha monthly plan", so likely 1 plan.

                    $plan = Plans::first(); // Or specific logic

                    if ($plan) {
                        SubUser::updateOrCreate(
                            ['user_id' => $user->id, 'plan_id' => $plan->id],
                            [
                                'status' => 'active',
                                'session_remaining' => $plan->session_limit, // Reset sessions? Or add? usually monthly reset
                                'start_date' => now(),
                                'expiry_date' => now()->addMonth(), // Or use period_end from invoice
                                'updated_by' => 'stripe_webhook',
                                'session_used' => 0 // Reset used on renewal
                            ]
                        );
                        Log::info("Subscription updated for user: {$user->id}");
                    } else {
                        Log::error("No Plan found in DB to link with Stripe subscription.");
                    }
                }
            }
        }
    }
}
