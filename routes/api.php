<?php

use App\Http\Controllers\Api\ApiTestController;
use App\Http\Controllers\Api\Client\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('/', function () {
    return response()->json([
        'unauthenticated',
    ], 403);
})->name('api.unauthenticated');

Route::group(['prefix' => 'test'], function () {
    Route::get('/', [ApiTestController::class, 'index']);
});
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');

});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::group(['prefix' => 'client'], function () {
        Route::group(['prefix' => 'profile'], function () {
            Route::get('/', [AuthController::class, 'index']);
            Route::post('/update', [AuthController::class, 'update']);
            Route::post('/password', [AuthController::class, 'updatePassword']);
        });
        Route::group(['prefix' => 'setting'], function () {
            Route::group(['prefix' => '2fa'], function () {
                Route::get('/', [AuthController::class, 'gAuth']);
                Route::post('/store', [AuthController::class, 'gAuthPost']);
                Route::post('/login', [AuthController::class, 'gAuthPostLogin']);
            });
        });
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});