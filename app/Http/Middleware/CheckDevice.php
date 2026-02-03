<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDevice
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
                'status' => false,
                'message' => 'Unauthorized',
                'logout' => true
            ], 401);
        }
        $current_device_id = $request->header('device_id');
        if($user->device_id != $current_device_id){
            $user->token()->revoke();
            return response()->json([
                'status' => false,
                'message' => 'You are logged in on another device. Please login again.',
                'logout' => true
            ], 401);
        }
        return $next($request);
    }
}
