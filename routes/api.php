<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiTestController;
use App\Http\Controllers\Api\Client\Auth\AuthController;
use App\Http\Controllers\Api\Client\ApiVipClientController;
use App\Http\Controllers\Api\Client\Header\ApiHeaderClientController;
use App\Http\Controllers\Api\Client\Mining\ApiMiningClientController;
use App\Http\Controllers\Api\Client\Wallet\ApiWalletClientController;
use App\Http\Controllers\Api\Client\Trading\ApiTradingClientController;
use App\Http\Controllers\Api\Client\Trading\ApiTradingClientControllerDemo;

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

Route::group(['prefix' => 'trading'], function () {
    Route::post('/post-price', [ApiTradingClientController::class, 'storeCryptoPrice']);
    Route::get('/get-price', [ApiTradingClientController::class, 'getTradePrice']);
    Route::get('/get-streak', [ApiTradingClientController::class, 'getStreak']);
});


Route::group(['prefix' => 'test'], function () {
    Route::get('/', [ApiTestController::class, 'index']);
});
Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('forgot', 'postForgot');
    Route::post('new-password', 'postPasswordChange');

});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::group(['prefix' => 'client'], function () {
        Route::prefix('header')->group(function () {
            Route::get('/get-header', [ApiHeaderClientController::class, 'index']);
        });
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/details', [ApiTradingClientController::class, 'tradeDashboard']);

        });
        Route::group(['prefix' => 'trading'], function () {
            Route::get('/histories', [ApiTradingClientController::class, 'index']);
            Route::get('/histories-open', [ApiTradingClientController::class, 'indexOpen']);
            Route::post('/post-trade/{id}', [ApiTradingClientController::class, 'store']);
            Route::get('/trading-form', [ApiTradingClientController::class, 'tradingForm']);
            Route::get('/trading-open-count', [ApiTradingClientController::class, 'getOpenTradeCount']);

            Route::group(['prefix' => 'demo'], function () {
                Route::get('/histories', [ApiTradingClientControllerDemo::class, 'index']);
                Route::get('/histories-open', [ApiTradingClientControllerDemo::class, 'indexOpen']);
                Route::get('/get-balance', [ApiTradingClientControllerDemo::class, 'getBalance']);
                Route::post('/post-trade/{id}', [ApiTradingClientControllerDemo::class, 'store']);
                Route::post('/post-reset', [ApiTradingClientControllerDemo::class, 'postReset']);
                Route::get('/trading-form', [ApiTradingClientControllerDemo::class, 'tradingForm']);
            });


        });

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

        Route::group(['prefix' => 'wallet'], function () {
            Route::get('/', [ApiWalletClientController::class, 'index']);
            Route::get('/balance', [ApiWalletClientController::class, 'getBalance']);
            Route::group(['prefix' => 'deposit'], function () {
                Route::get('/get-address', [ApiWalletClientController::class, 'getDepositAddress']);
                Route::post('/create-address', [ApiWalletClientController::class, 'createDepositAddress']);
            });
            Route::group(['prefix' => 'withdraw'], function () {
                // Route::get('/get-histories', [ApiWalletClientController::class, 'getDepositAddress']);
                Route::post('/post', [ApiWalletClientController::class, 'postWithdraw']);
            });
            Route::group(['prefix' => 'transfer'], function () {
                Route::post('/post', [ApiWalletClientController::class, 'transferPost']);
            });
            Route::group(['prefix' => 'exchange'], function () {
                Route::get('/get-balance', [ApiWalletClientController::class, 'getTradingBalance']);
                Route::post('/post-to-wallet', [ApiWalletClientController::class, 'exchangeToWallet']);
                Route::post('/post-to-live', [ApiWalletClientController::class, 'exchangeToLive']);
                Route::get('/histories', [ApiWalletClientController::class, 'getHistories']);
            });
        });

        Route::group(['prefix' => 'vip'], function () {
            Route::post('/post', [ApiVipClientController::class, 'store']);
            Route::get('/detail', [ApiVipClientController::class, 'index']);
            Route::get('/', [ApiVipClientController::class, 'getVip']);
            Route::post('/upgrade', [ApiVipClientController::class, 'upgradeVip']);
            Route::get('/referral', [ApiVipClientController::class, 'getReferrals']);
            Route::get('/commission', [ApiVipClientController::class, 'getCommission']);
        });
        Route::prefix('mining')->group(function () {
            Route::controller(ApiMiningClientController::class)->group(function () {
                Route::get('get-mining', 'index');
                Route::post('post-mining', 'store');
                Route::get('get-mining-list', 'show');
                Route::get('get-profit', 'getProfit');
            });
        });
        Route::get('/asset-balance', [ApiWalletClientController::class, 'getBalanceAsset']);
        Route::post('/get-session', [AuthController::class, 'getUserSession']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});