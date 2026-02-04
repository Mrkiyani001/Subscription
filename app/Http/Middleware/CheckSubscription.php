<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();
        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }
        $subscription = $user->hasActiveSubscription();
        if(!$subscription){
            return response()->json([
                'success' => false,
                'message' => 'First You need to subscribe',
                'data' => null,
            ], 403);
        }
        return $next($request);
    }
}
