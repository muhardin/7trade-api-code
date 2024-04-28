<?php

namespace App\Http\Controllers\Api\Client\Mining;

use App\Http\Controllers\Controller;
use App\Models\Mining;
use App\Models\User;
use App\Models\UserMining;
use App\Models\UserSession;
use App\Models\Wallet;
use App\Models\WalletMining;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiMiningClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mining = Mining::get();
        return response()->json([
            'mining' => $mining,
            'message' => 'Success',
        ], 200);
    }

    public function store(Request $request)
    {
        $header = $request->header('Authorization');
        $session = UserSession::where('token', $header)->first();
        if (!@$session) {
            return response()->json([
                'message' => 'No Session Found', 'token' => $header],
                201);
        }
        $user = User::find($session->user_id);
        if (!$user) {
            return response()->json(['message' => 'Access', 'error' => 'Invalid Account Access'], 201);
        }
        $validator = Validator::make($request->all(), [
            'miningId' => 'required|exists:minings,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 201);
        }

        $mining = Mining::findOrFail($request->miningId);
        $amount = $mining->price * $request->amount;
        $daily_profit = $mining->daily_profit * $request->amount;
        $serviceFee = $mining->service_fee * $request->amount;

        $checkMaximum = UserMining::where('mining_id', $mining->id)->where('user_id', $user->id)->sum('count');
        if ($checkMaximum > 0) {
            return response()->json([
                'message' => 'You have reached the maximum limit for this mining plan.',
                'error' => 'Maximum',
            ], 201);
        }

        if (walletBalance($user->id) < $amount) {
            return response()->json([
                'message' => 'Insufficient balance of your wallet',
                'error' => 'Balance'], 201);
        }

        $currentDate = Carbon::now();
        $futureDate = $currentDate->addDays($mining->period);
        $active_date = $currentDate->addDays(1);

        $trx = Str::uuid();

        $userMining = new UserMining();
        $userMining->user_id = $user->id;
        $userMining->mining_id = $mining->id;
        $userMining->amount = $amount;
        $userMining->daily_profit = $daily_profit;
        $userMining->daily_value = $mining->daily_profit;
        $userMining->service_fee = $serviceFee;
        $userMining->count = $request->amount;
        $userMining->expire_date = $futureDate;
        $userMining->active_date = $active_date;
        $userMining->price = $mining->price;
        $userMining->status = 'Processing';
        $userMining->save();

        $walletSender = new Wallet();
        $walletSender->user_id = $user->id;
        $walletSender->trx = @$trx;
        $walletSender->amount = $amount;
        $walletSender->description = 'Mining Order';
        $walletSender->type = 'Out';
        $walletSender->save();

        return response()->json(['message' => 'Success', 'data' => $mining->daily_profit], 200);

    }

    public function show(Request $request)
    {
        $header = $request->header('Authorization');
        $session = UserSession::where('token', $header)->first();
        if (!@$session) {
            return response()->json([
                'message' => 'No Session Found'],
                201);
        }
        $user = User::find($session->user_id);
        if (!$user) {
            return response()->json(['message' => 'Access', 'error' => 'Invalid Account Access'], 201);
        }
        $mining = UserMining::with(['Mining'])->orderBy('id', 'desc')->where('user_id', $user->id)->paginate(25);
        return response()->json([
            'mining' => $mining,
            'message' => 'Success',
        ], 200);
    }
    public function getProfit(Request $request)
    {
        $header = $request->header('Authorization');
        $session = UserSession::where('token', $header)->first();
        if (!@$session) {
            return response()->json([
                'message' => 'No Session Found'],
                201);
        }
        $user = User::find($session->user_id);
        if (!$user) {
            return response()->json(['message' => 'Access', 'error' => 'Invalid Account Access'], 201);
        }
        $mining = WalletMining::where('user_id', $user->id)->paginate(25);
        return response()->json([
            'mining' => $mining,
            'message' => 'Success',
        ], 200);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

    }
}
