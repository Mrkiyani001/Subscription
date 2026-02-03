<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group([
    'middleware' => ['api', 'auth:api', 'check.device']], function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refreshtoken']);
    Route::get('/users', [AuthController::class, 'getusers']);
});
