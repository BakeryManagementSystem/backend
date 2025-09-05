<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);  // public list
Route::post('/products', [ProductController::class, 'store']); // owner upload
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('health', fn () => response()->json(['ok' => true]));
