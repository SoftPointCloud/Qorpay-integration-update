<?php

use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StoreLocationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::namespace('Api')->group(function () {
    Route::get('/store-locations', [StoreLocationController::class, 'index']);
    Route::get('/store-locations/{code}', [StoreLocationController::class, 'show']);
    Route::get('/order/{id}/clear', [OrderController::class, 'clear']);
    Route::get('/store/{store_code}/menus', [StoreController::class, 'menus']);
    Route::get('/store/{store_code}/meal-plan-stats', [StoreController::class, 'mealPlanStats']);
    Route::get('/user/exists', [UserController::class, 'exists']);
    Route::post('/newsletter/register', [NewsletterController::class, 'register']);
    Route::get('/cart/{id}', [StoreLocationController::class, 'cart']);
    Route::get('/carts', [StoreLocationController::class, 'carts']);
    Route::get('/cart-total', [StoreLocationController::class, 'total']);
    Route::get('/createtoken', [StoreLocationController::class, 'createtoken']);


});
Route::fallback(function() {
    return response()->json([
        'data' => [],
        'success' => false,
        'status' => 404,
        'message' => 'Invalid Route'
    ]);
});