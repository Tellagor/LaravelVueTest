<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/organization', [OrganizationController::class, 'show']);
    Route::post('/organization', [OrganizationController::class, 'store']);
});
