<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes that need auth but NOT subscription (can subscribe here)
Route::group(['middleware' => ['api', 'auth:api', 'check.device']], function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refreshtoken']);
    Route::get('/users', [AuthController::class, 'getusers']);

    // Subscription Plan Routes (Admin - no subscription needed to manage plans)
    Route::post('/create/plan', [SubscriptionPlanController::class, 'createPlan']);
    Route::get('/get/all/plans', [SubscriptionPlanController::class, 'getAllSubscriptionPlans']);
    Route::post('/get/plan', [SubscriptionPlanController::class, 'getSubscriptionPlanById']);
    Route::post('/update/plan', [SubscriptionPlanController::class, 'updateSubscriptionPlan']);
    Route::delete('/delete/plan', [SubscriptionPlanController::class, 'deleteSubscriptionPlan']);
    
    // Subscribe route (user can subscribe without having subscription)
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/current/subscription', [SubscriptionController::class, 'currentSubscription']);
});

// Routes that need BOTH auth AND active subscription
Route::group(['middleware' => ['api', 'auth:api', 'check.device', 'check.subscription']], function () {
    // Subscription Actions (need active subscription to use these features)
    Route::post('/use/session', [SubscriptionController::class, 'UseSession']);
    Route::get('/session/history', [SubscriptionController::class, 'SessionHistory']);
    Route::get('/unsubscribe', [SubscriptionController::class, 'cancelSubscription']);
    Route::get('/extend/subscription', [SubscriptionController::class, 'extendSubscription']);
});
