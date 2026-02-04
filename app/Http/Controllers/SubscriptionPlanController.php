<?php

namespace App\Http\Controllers;

use App\Models\Plans;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanController extends BaseController
{
    public function createPlan(Request $request)
    {
        $this->ValidateRequest($request, [
            'name' => 'string|required',
            'slug' => 'string|required|unique:subscription_plans,slug',
            'description' => 'string|nullable',
            'session_limit' => 'integer|required',
            'duration_days' => 'integer|required',
            'price' => 'integer|nullable',
        ]);
        try {
            DB::beginTransaction();
            $user = auth('api')->user();
            if(!$user){
                return $this->NotAuthorized();
            }
            $plan = Plans::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'session_limit' => $request->session_limit,
                'duration_days' => $request->duration_days,
                'price' => $request->price,
                'created_by' => $user->id,
            ]);
            DB::commit();
            return $this->Response(true, $plan, 'Plan created successfully', 201);
        } catch (Exception $e) {
            DB::rollBack();
        return $this->Response(false, null, 'Plan creation failed', 500);
    }
    }
    public function getAllSubscriptionPlans(Request $request)
    {
        try{
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $plans = Plans::all();
        return $this->Response(true, $plans, 'Plans fetched successfully', 200);
    }catch(Exception $e){
        return $this->Response(false, null, 'Plans fetching failed', 500);
    }
    }
    public function getSubscriptionPlanById(Request $request)
    {
        $this->ValidateRequest($request, [
            'id' => 'required|integer',
        ]);
        try{
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $plan = Plans::find($request->id);
        if(!$plan){
            return $this->Response(false, null, 'Plan not found', 404);
        }
        return $this->Response(true, $plan, 'Plan fetched successfully', 200);
    }catch(Exception $e){
        return $this->Response(false, null, 'Plan fetching failed', 500);
    }
    }
    public function updateSubscriptionPlan(Request $request)
    {
        $this->ValidateRequest($request, [
            'id' => 'required|integer',
            'name' => 'string|nullable',
            'slug' => 'string|nullable|unique:subscription_plans,slug,'.$request->id,
            'description' => 'string|nullable',
            'session_limit' => 'integer|nullable',
            'duration_days' => 'integer|nullable',
            'price' => 'integer|nullable',
        ]);
        try{
        DB::beginTransaction();
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $plan = Plans::find($request->id);
        if(!$plan){
            return $this->Response(false, null, 'Plan not found', 404);
        }
        
        // Only update fields that are provided
        $allowedFields = ['name', 'slug', 'description', 'session_limit', 'duration_days', 'price'];
        $updateData = [];
        
        foreach($allowedFields as $field){
            if($request->has($field)){
                $updateData[$field] = $request->$field;
            }
        }
        
        $updateData['updated_by'] = $user->id;
        
        $plan->update($updateData);
        DB::commit();

        return $this->Response(true, $plan, 'Plan updated successfully', 200);
    }catch(Exception $e){
        DB::rollBack();
        return $this->Response(false, null, 'Plan updating failed', 500);
    }
    }
    public function deleteSubscriptionPlan(Request $request)
    {
        $this->ValidateRequest($request, [
            'id' => 'required|integer',
        ]);
        try{
        $user = auth('api')->user();
        if(!$user){
            return $this->NotAuthorized();
        }
        $plan = Plans::find($request->id);
        if(!$plan){
            return $this->Response(false, null, 'Plan not found', 404);
        }
        
        // Check if plan has active subscriptions
        $activeSubscriptions = $plan->userSubscriptions()->where('status', 'active')->count();
        if($activeSubscriptions > 0){
            return $this->Response(false, null, 'Cannot delete plan with active subscriptions', 400);
        }
        
        $plan->delete();
        return $this->Response(true, null, 'Plan deleted successfully', 200);
    }catch(Exception $e){
        return $this->Response(false, null, 'Plan deletion failed', 500);
    }
    }
}
