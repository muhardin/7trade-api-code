<?php

namespace App\Http\Controllers\Api\Client\Header;

use App\Models\UserTrading;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiHeaderClientController extends Controller
{
          /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $balance = WalletTradingBalance(auth()->user()->id);
        if ($balance <= 0) {
            $bl = 0;
        } else {
            $bl = $balance;
        }
        
        $tradings = UserTrading::where('user_id', auth()->user()->id)->where('status', 'Open')->count();
        $streak   = getStreakAmount();
        
        return response()->json([
            'tradings'    => $tradings,
            'pool'        => getStreakAmount(),
            'streak'      => number_format($streak,2),
            'balance'     => $bl,
            'balanceDemo' => WalletTradingBalanceDemo(auth()->user()->id),
            'message'     => 'Success',
        ], 200);
    }

}
