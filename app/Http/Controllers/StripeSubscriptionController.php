<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class StripeSubscriptionController extends BaseController
{
    public function checkout(Request $request)
    {
        $this->ValidateRequest($request, [
            'price_id' => 'required|string',
        ]);
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $priceId = $request->price_id;

        try {
            $checkout = $user->newSubscription('default', $priceId)  // build in helper function of cashier calls in user model
                ->allowPromotionCodes()
                ->withMetadata([
                    'user_id' => $user->id,
                    'price_id' => $priceId,
                    'source' => 'api_checkout'
                ])->checkout([
                    'success_url' => url('/') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => url('/'),
                ]);
            return $this->Response(true, $checkout, 'Checkout successful', 200);
        } catch (Exception $e) {
            return $this->Response(false, null, 'Checkout failed', 500);
        }
    }

    /**
     * Get the Stripe Billing Portal URL.
     */
    public function billingPortal(Request $request)
    {
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        try {
            return response()->json([
                'url' => $user->billingPortalUrl(url('/'))
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
