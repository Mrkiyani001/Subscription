<?php

namespace App\Http\Controllers;

use App\Models\Plans;
use App\Models\SubSession;
use App\Models\SubUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends BaseController
{
    // This method is commented out to enforce payment via Stripe.
    // Use /api/stripe/checkout instead.
    public function subscribe(Request $request){        // for FREE Subscription
        $this->ValidateRequest($request, [
            'plan_id' => 'required|integer',
        ]);
        try{
            $user = auth('api')->user();
            if(!$user){
                return $this->NotAuthorized();
            }
            if($user->activeSubscription()){
                return $this->Response(false, null, 'User already has an active subscription', 400);
            }
            $plan = Plans::find($request->plan_id);
            if(!$plan){
                return $this->Response(false, null, 'Plan not found', 404);
            }
            DB::beginTransaction();
            $subscription = SubUser::create([
                'user_id' => $user->id,
                'plan_id' => $request->plan_id,
                'session_used' => 0,
                'session_remaining' => $plan->session_limit,
                'start_date' => today(),
                'expiry_date' => today()->addDays($plan->duration_days),
                'status' => 'active',
                'created_by' => $user->id,
            ]);
            $subscription->load('plan');
            DB::commit();
            return $this->Response(true, $subscription, 'Subscription successful', 200);
        }catch(Exception $e){
            DB::rollBack();
            return $this->Response(false, null, 'Subscription failed', 500);
        }
    }
    public function currentSubscription(Request $request){
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $subscription = $user->activeSubscription();
        if(!$subscription){
            return $this->Response(false, null, 'No active subscription found', 404);
        }
        $subscription->load('plan');
        return $this->Response(true, $subscription, 'Active subscription found', 200);
    }
    public function UseSession(Request $request){
        $this->ValidateRequest($request, [
            'session_type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);
        try{
            $user = auth('api')->user();
            if(!$user){
                return $this->NotAuthorized();
            }
            $subscription = $user->activeSubscription();
            if(!$subscription){
                return $this->Response(false, null, 'No active subscription found', 404);
            }
            if(!$subscription->canUseSession()){
                return $this->Response(false, null, 'Plan is not active or expired', 400);
            }
            DB::beginTransaction();
            $subscription->increment('session_used');
            $subscription->decrement('session_remaining');

            SubSession::create([
                'user_id' => $user->id,
                'session_type' => $request->session_type,
                'metadata' => $request->metadata,
                'used_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $subscription->load('plan');
            DB::commit();
            return $this->Response(true, $subscription, 'Session used successfully', 200);
        }catch(Exception $e){
            DB::rollBack();
            return $this->Response(false, null, 'Session usage failed', 500);
        }
    }
    public function SessionHistory(Request $request){
        try{
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $subscription = $user->activeSubscription();
        if(!$subscription){
            return $this->Response(false, null, 'No active subscription found', 404);
        }
        $sessions = SubSession::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        return $this->Response(true, $sessions, 'Session history found', 200);
    }catch(Exception $e){
        return $this->Response(false, null, 'Session history not found', 500);
    }
    }
    public function cancelSubscription(Request $request){
        try{
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $subscription = $user->activeSubscription();
        if(!$subscription){
            return $this->Response(false, null, 'No active subscription found', 404);
        }
        DB::beginTransaction();
        
        // Handle Stripe Subscription Cancellation
        if ($user->subscription('default')) {
            $user->subscription('default')->cancel(); // Stops auto-renewal at period end
        }

        $subscription->update([
            'status' => 'inactive',
            'updated_by' => $user->id,
        ]);
        DB::commit();
        return $this->Response(true, $subscription, 'Subscription canceled. Access remains until end of period.', 200);
    }catch(Exception $e){
        DB::rollBack();
        return $this->Response(false, null, 'Subscription cancellation failed: ' . $e->getMessage(), 500);
    }
}
public function extendSubscription(Request $request){
    try{
    $user = auth('api')->user();
    if(!$user){
        return $this->NotAuthorized();
    }
    $subscription = $user->activeSubscription();
    if(!$subscription){
        return $this->Response(false, null, 'No active subscription found', 404);
    }
    if($subscription->status != 'active'){
        return $this->Response(false, null, 'First You need to subscribe', 400);
    }
    DB::beginTransaction();
    
    // Load plan relationship
    $subscription->load('plan');
    
    $subscription->update([
        'session_remaining' => $subscription->session_remaining + $subscription->plan->session_limit,
        'expiry_date' => $subscription->expiry_date->addDays($subscription->plan->duration_days),
        'updated_by' => $user->id,
    ]);
    DB::commit();
    return $this->Response(true, $subscription, 'Subscription extended successfully', 200);
}catch(Exception $e){
    DB::rollBack();
    return $this->Response(false, null, 'Subscription extension failed', 500);
}
}
}
