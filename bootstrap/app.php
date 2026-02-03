<?php

use App\Http\Middleware\CheckDevice;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Prevent redirect to login route for API requests
        $middleware->redirectGuestsTo(function ($request) {
            // For API requests, don't redirect - let AuthenticationException be thrown
            return null;
        });
        
        // Register custom middleware aliases
        $middleware->alias([
            'check.device' => CheckDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle Authentication Exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                    'status' => false,
                ], 401);
            }
        });

        // Handle Not Found Exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Record not found.',
                    'status' => false,
                ], 404);
            }
        });

        // Handle Validation Exceptions
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Validation failed.',
                    'errors' => $e->errors(),
                    'status' => false,
                ], 422);
            }
        });

        // Handle Authorization Exceptions
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthorized action.',
                    'status' => false,
                ], 403);
            }
        });

        // Handle All Other HTTP Exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'An error occurred.',
                    'status' => false,
                ], $e->getStatusCode());
            }
        });

        // Handle Generic Exceptions (fallback)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // In production, don't expose detailed error messages
                $message = config('app.debug') ? $e->getMessage() : 'An error occurred.';
                
                return response()->json([
                    'message' => $message,
                    'status' => false,
                ], 500);
            }
        });
    })->create();