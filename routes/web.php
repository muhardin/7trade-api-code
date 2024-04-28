<?php

use App\Http\Controllers\CallBackController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
 */

Route::get('/', function () {
    return view('welcome');
});

Route::get('send-mail', function () {
    $firstName = 'Dex';
    $lastname = 'D.';
    $email = 'dexgame88@gmail.com';
    $content = '<div
        style="font-family:`Helvetica Neue`,Arial,sans-serif;font-size:16px;line-height:22px;text-align:left;color:#555;">
        Hello ' . $firstName . ' ' . @$lastname . '!<br></br>
        Thank you for signing up for 7Trade. We`re really happy to have
        you! Click the link below to login to your account:
    </div>';
    \Mail::to($email)->bcc('muhardin@gmail.com')->send(new \App\Mail\WelcomeMail($content, "Welcome"));
    dd("Email is Sent.");
});
Route::get('test', [TestController::class, 'index']);
Route::get('test-emmit', [TestController::class, 'emitEvent']);
Route::post('/v2.0/deposit-callback', [CallBackController::class, 'handle']);
Route::post('/callback/alphapays', [CallBackController::class, 'handle']);
Route::get('/callback/alphapays', [CallBackController::class, 'handle']);