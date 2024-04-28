<?php

namespace App\Http\Controllers\Api\Client\Trading;

use App\Http\Controllers\Controller;
use App\Models\CryptoPrice;
use App\Models\StreakChallenge;
use App\Models\User;
use App\Models\UserSession;
use App\Models\UserTrading;
use App\Models\UserTradingDemo;
use App\Models\WalletAdmin;
use App\Models\WalletTrading;
use App\Models\WalletTradingDemo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiTradingClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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
        if (@$_GET['limit']) {
            $tradings = UserTrading::where('user_id', $user->id)->where('status', 'Closed')->orderby('id', 'desc')->paginate($_GET['limit']);
            return response()->json([
                'items' => $tradings,
                'message' => 'Success',
            ],
                200);
        } else {
            $tradings = UserTrading::where('user_id', $user->id)->where('status', 'Closed')->orderby('id', 'desc')->paginate(10);
            return response()->json([
                'items' => $tradings,
                'message' => 'Success',
            ],
                200);
        }

    }
    public function tradingForm()
    {
        $tradings = UserTrading::where('status', 'open')->orderby('id', 'desc')->get();
        //setup the percentage
        $count = $tradings->count();
        $countBuy = $tradings->where('action', 'Buy')->count();
        $countSell = $tradings->where('action', 'Sell')->count();
        $percentageBuy = 0;
        $percentageSell = 0;

        if ($count > 0) {
            $percentageBuy = number_format(($countBuy / $count) * 100, 2);
            $percentageSell = number_format(($countSell / $count) * 100, 2);
        }

        return response()->json([
            'items' => $tradings,
            'percentageBuy' => $percentageBuy,
            'percentageSell' => $percentageSell,
            'message' => 'Success',
        ],
            200);

    }
    public function indexOpen()
    {
        if (@$_GET['limit']) {
            $tradings = UserTrading::where('user_id', auth()->user()->id)->where('status', 'open')->orderby('id', 'desc')->paginate($_GET['limit']);
            return response()->json([
                'items' => $tradings,
                'message' => 'Success',
            ],
                200);
        } else {
            $tradings = UserTrading::where('user_id', auth()->user()->id)->where('status', 'open')->orderby('id', 'desc')->paginate(10);
            return response()->json([
                'items' => $tradings,
                'message' => 'Success',
            ],
                200);
        }

    }
    public function tradeDashboard(Request $request)
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
        $tradings = UserTrading::where('user_id', $user->id)->whereIn('action', ['Buy', 'Sell'])->orderby('id', 'desc')->get();

        $totalRevenue = $tradings->sum('final_amount');
        $totalProfit = $tradings->sum('profit_amount');

        $totalTrade = $tradings->count();
        $totalTradeAmount = $tradings->sum('amount');
        $totalWinRound = $tradings->where('final_amount', '>', 0)->count();
        $totalLoseRound = $tradings->where('final_amount', '<=', 0)->count();
        $totalDrawRound = $tradings->where('round', '=', 'Draw')->count();

        $totalBuy = $tradings->where('action', 'Buy')->sum('amount');
        $totalSell = $tradings->where('action', 'Sell')->sum('amount');
        $percentBuy = ($totalBuy / $totalTradeAmount) * 100;
        $percentSell = ($totalSell / $totalTradeAmount) * 100;

        return response()->json([
            'items' => $tradings,
            'totalTrade' => $totalTrade,
            'totalTradeAmount' => $totalTradeAmount,
            'totalWinRound' => $totalWinRound,
            'totalLoseRound' => $totalLoseRound,
            'totalDrawRound' => $totalDrawRound,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'percentBuy' => number_format($percentBuy, 2),
            'percentSell' => number_format($percentSell, 2),
            'message' => 'Success',
        ],
            200);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:5',
        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), 'message' => 'Invalid Amount'], 201);
        }

        // return response()->json(['message' => $id], 201);

        if (WalletTradingBalance(auth()->user()->id) < $request->amount) {
            return response()->json(['message' => 'Invalid Amount', 'error' => 'Insufficient balance'], 201);
        }
        $trx = Str::uuid();
        $profit_amount = $request->amount * (70 / 100);
        $final_amount = $request->amount + $profit_amount;
        $profit_value = 70 / 100;

        $userTrading = new UserTrading();
        $userTrading->trx = $trx;
        $userTrading->user_id = auth()->user()->id;
        $userTrading->amount = $request->amount;
        $userTrading->action = ucfirst($id);
        $userTrading->profit_value = $profit_value;
        $userTrading->profit_amount = $profit_amount;
        $userTrading->final_amount = $request->amount + $profit_amount;
        $userTrading->status = open;
        $userTrading->save();

        $exchange = new WalletTrading();
        $exchange->trx = $trx;
        $exchange->user_id = auth()->user()->id;
        $exchange->type = 'Out';
        $exchange->amount = $request->amount;
        $exchange->description = 'Trading Action';
        $exchange->save();

        $streak = new StreakChallenge();
        $streak->amount = (0.05 / 100) * $request->amount;
        $streak->value = 0.05;
        $streak->save();

        return response()->json(['message' => 'Success'], 200);

    }

    /**
     * Display the specified resource.
     */
    public function storeCryptoPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candlestickData' => 'required',
            'currentTime' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input', 'error' => $validator->errors()], 201);
        }
        // $rowsToDelete = CryptoPrice::orderByDesc('id')->first();
        // $rowSCount    = $rowsToDelete->id - 180;
        // CryptoPrice::where('id','<=',$rowSCount)->delete();

        $formattedData = $request->input('candlestickData');
        $currentTime = $request->input('currentTime');
        foreach ($formattedData as $data) {
            // Check if data with the same timestamp exists
            $existingData = CryptoPrice::where('timestamp', $data['timestamp'])->first();
            if (!$existingData) {

                /** Code For Trading Profit Calculation */
                // logic for update win or lose trading
                /** If data with the timestamp doesn't exist, create a new record but create logic for win or lose the preview data */
                //check the last Data
                $tradingCount = UserTrading::where('status', 'open')->count();
                if (@$tradingCount > 0) {
                    $checkLastPrev = CryptoPrice::orderBy('id', 'desc')->skip(1)->first();
                    $checkLast = CryptoPrice::orderBy('id', 'desc')->first();

                    //check the trade put
                    $tradingBuy = UserTrading::where('status', 'open')->where('action', 'Buy')->sum('amount');
                    $tradingSell = UserTrading::where('status', 'open')->where('action', 'Sell')->sum('amount');
                    if ($tradingBuy < @$tradingSell) {
                        $setProfit = 'Buy';
                        if ($checkLast->close < $checkLastPrev->close) {
                            $newClose = $checkLastPrev->close + 0.01;
                            \DB::table('crypto_prices')->where('id', $checkLast->id)->update(['close' => $newClose, 'is_updated' => 'Yes']);
                        } else {
                            $newClose = $checkLast->close;
                        }

                        //insert wallet_admins
                        $trxWalletAdmin = Str::uuid();
                        $tradingBuyFinal = UserTrading::where('status', 'open')->where('action', 'Buy')->sum('final_amount');
                        $profitAdmin = $tradingSell - $tradingBuyFinal;
                        $finalProfit = $profitAdmin - ($profitAdmin * 0.2);

                        $walletAdmin = new WalletAdmin();
                        $walletAdmin->trx = $trxWalletAdmin;
                        $walletAdmin->type = 'In';
                        $walletAdmin->amount = $profitAdmin;
                        $walletAdmin->description = 'Company Profit';
                        $walletAdmin->save();

                        /** end of wallet admin */

                        \DB::table('user_tradings')->where('status', 'open')->where('action', 'Sell')->update(['is_profit' => 'No', 'status' => 'closed', 'final_amount' => 0, 'close_price' => $newClose, 'base_price' => $checkLastPrev->close]);

                        $tradingProfit = UserTrading::where('status', 'open')->where('action', 'Buy')->get();
                        foreach ($tradingProfit as $item) {
                            \DB::table('user_tradings')->where('id', $item->id)->update(['is_profit' => 'Yes', 'status' => 'closed', 'close_price' => $newClose, 'base_price' => $checkLastPrev->close]);
                            //setup wallet profit
                            $trx = Str::uuid();
                            $exchange = new WalletTrading();
                            $exchange->trx = $trx;
                            $exchange->user_id = $item->user_id;
                            $exchange->type = 'In';
                            $exchange->amount = $item->final_amount;
                            $exchange->description = 'Profit Trading';
                            $exchange->save();
                            //later setup email notification
                        }

                    } elseif ($tradingBuy == @$tradingSell) {
                        $tradingProfit = UserTrading::where('status', 'open')->get();
                        foreach ($tradingProfit as $item) {
                            \DB::table('user_tradings')->where('id', $item->id)->update(['is_profit' => 'Yes', 'status' => 'closed']);
                            //setup wallet profit
                            $trx = Str::uuid();
                            $exchange = new WalletTrading();
                            $exchange->trx = $trx;
                            $exchange->user_id = $item->user_id;
                            $exchange->type = 'In';
                            $exchange->amount = $item->amount;
                            $exchange->description = 'Profit Trading Draw Price';
                            $exchange->save();
                            //later setup email notification
                        }

                    } else {
                        $setProfit = 'Sell';
                        if ($checkLast->close > $checkLastPrev->close) {
                            $between = $checkLast->close - $checkLastPrev->close;
                            $add = $between + 0.01;
                            $newClose = $checkLastPrev->close - $add;
                            \DB::table('crypto_prices')->where('id', $checkLast->id)->update(['close' => $newClose, 'is_updated' => 'Yes']);
                        } else {
                            $newClose = $checkLast->close;
                        }
                        //insert wallet_admins
                        $trxWalletAdmin = Str::uuid();
                        $tradingSellFinal = UserTrading::where('status', 'open')->where('action', 'Sell')->sum('final_amount');
                        $profitAdmin = $tradingBuy - $tradingSellFinal;
                        $finalProfit = $profitAdmin - ($profitAdmin * 0.2);
                        $walletAdmin = new WalletAdmin();
                        $walletAdmin->trx = $trxWalletAdmin;
                        $walletAdmin->type = 'In';
                        $walletAdmin->amount = $finalProfit;
                        $walletAdmin->description = 'Company Profit';
                        $walletAdmin->save();
                        /** end of wallet admin */
                        \DB::table('user_tradings')->where('status', 'open')->where('action', 'Buy')->update(['is_profit' => 'No', 'status' => 'closed', 'final_amount' => 0, 'close_price' => $newClose, 'base_price' => $checkLastPrev->close]);

                        $tradingProfit = UserTrading::where('status', 'open')->where('action', 'Sell')->get();
                        foreach ($tradingProfit as $item) {
                            \DB::table('user_tradings')->where('id', $item->id)->update(['is_profit' => 'Yes', 'status' => 'closed', 'close_price' => $newClose, 'base_price' => $checkLastPrev->close]);
                            //setup wallet profit
                            $trx = Str::uuid();
                            $exchange = new WalletTrading();
                            $exchange->trx = $trx;
                            $exchange->user_id = $item->user_id;
                            $exchange->type = 'In';
                            $exchange->amount = $item->final_amount;
                            $exchange->description = 'Profit Trading';
                            $exchange->save();
                            //later setup email notification
                        }
                    }
                }
                /** End Of ode For Trading Profit Calculation */

                /** Code For Trading Profit Calculation of Demo */
                $tradingCount = UserTradingDemo::where('status', 'open')->count();
                if (@$tradingCount > 0) {
                    $checkLastPrev = CryptoPrice::orderBy('id', 'desc')->skip(1)->first();
                    $checkLast = CryptoPrice::orderBy('id', 'desc')->first();

                    if ($checkLast->close > $checkLastPrev->close) {
                        \DB::table('user_trading_demos')->where('status', 'open')->where('action', 'Sell')->update(['is_profit' => 'No', 'status' => 'closed', 'final_amount' => 0]);
                        $tradingProfit = UserTradingDemo::where('status', 'open')->where('action', 'Buy')->get();
                        foreach ($tradingProfit as $item) {
                            \DB::table('user_trading_demos')->where('id', $item->id)->update(['is_profit' => 'Yes', 'status' => 'closed']);
                            //setup wallet profit
                            $trx = Str::uuid();
                            $exchange = new WalletTradingDemo();
                            $exchange->trx = $trx;
                            $exchange->user_id = $item->user_id;
                            $exchange->type = 'In';
                            $exchange->amount = $item->final_amount;
                            $exchange->description = 'Profit Trading';
                            $exchange->save();
                            //later setup email notification
                        }
                    } elseif ($checkLast->close == $checkLastPrev->close) {
                        $tradingProfit = UserTradingDemo::where('status', 'open')->get();
                        foreach ($tradingProfit as $item) {
                            \DB::table('user_trading_demos')->where('id', $item->id)->update(['is_profit' => 'Yes', 'status' => 'closed']);
                            //setup wallet profit
                            $trx = Str::uuid();
                            $exchange = new WalletTradingDemo();
                            $exchange->trx = $trx;
                            $exchange->user_id = $item->user_id;
                            $exchange->type = 'In';
                            $exchange->amount = $item->amount;
                            $exchange->description = 'Profit Trading Draw Price';
                            $exchange->save();
                            //later setup email notification
                        }
                    } else {
                        \DB::table('user_trading_demos')->where('status', 'open')->where('action', 'Buy')->update(['is_profit' => 'No', 'status' => 'closed', 'final_amount' => 0]);
                        $tradingProfit = UserTradingDemo::where('status', 'open')->where('action', 'Sell')->get();
                        foreach ($tradingProfit as $item) {
                            \DB::table('user_trading_demos')->where('id', $item->id)->update(['is_profit' => 'Yes', 'status' => 'closed']);
                            $trx = Str::uuid();
                            $exchange = new WalletTradingDemo();
                            $exchange->trx = $trx;
                            $exchange->user_id = $item->user_id;
                            $exchange->type = 'In';
                            $exchange->amount = $item->final_amount;
                            $exchange->description = 'Profit Trading';
                            $exchange->save();
                            //later setup email notification
                        }
                    }

                }
                /** End Of ode For Trading Profit Calculation of Demo*/

                $userTrading = new CryptoPrice();
                $userTrading->million_time = $currentTime;
                $userTrading->pair_id = 1;
                $userTrading->timestamp = $data['timestamp'];
                $userTrading->open = $data['open'];
                $userTrading->high = $data['high'];
                $userTrading->low = $data['low'];
                $userTrading->close = $data['close'];
                $userTrading->trading_crypto = $data['trading_crypto'];
                $userTrading->trading_fiat = $data['trading_fiat'];
                $userTrading->volume = $data['volume'];
                $userTrading->save();
            } else {

                $userTrading = CryptoPrice::find($existingData->id);

                $userTrading->million_time = $currentTime;
                $userTrading->pair_id = 1;
                $userTrading->timestamp = $data['timestamp'];
                $userTrading->open = $data['open'];
                $userTrading->high = $data['high'];
                $userTrading->low = $data['low'];
                $userTrading->trading_crypto = $data['trading_crypto'];
                $userTrading->trading_fiat = $data['trading_fiat'];
                if (@$userTrading->is_updated == 'No') {
                    $userTrading->close = $data['close'];
                }
                $userTrading->volume = $data['volume'];
                $userTrading->save();

            }
        }

        // Check if this timestamp is already in

        return response()->json(['message' => 'Success'], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function _getTradePrice()
    {
        // Initialize the query builder
        $query = CryptoPrice::orderBy('million_time', 'desc');

        // Apply condition based on the presence of 'startTime' parameter
        if (@$_GET['startTime']) {
            $query->where('million_time', '<=', $_GET['startTime']);
        }

        // Apply limit if 'limit' parameter is provided
        $limit = @$_GET['limit'] ?? 60;
        $getPrices = $query->limit($limit)->get(['timestamp', 'open', 'high', 'low', 'close', 'trading_crypto', 'trading_fiat', 'volume']);

        $formattedData = [];
        foreach ($getPrices as $price) {
            $formattedData[] = [
                $price['timestamp'],
                $price['open'],
                $price['high'],
                $price['low'],
                $price['close'],
                $price['trading_crypto'],
                $price['trading_fiat'],
                $price['volume'],
            ];
        }

        return response()->json([
            'data' => $formattedData,
            'message' => 'Success',
        ], 200);
    }
    public function getTradePrice()
    {
        if (@$_GET['startTime']) {
            if (@$_GET['limit']) {
                $totalCount = CryptoPrice::count();
                $offset = max(0, $totalCount-@$_GET['limit']); // Ensure offset is not negative
                $getPrices = CryptoPrice::orderBy('id', 'asc')
                    ->where('million_time', '<=', $_GET['startTime'])
                    ->skip($offset)
                    ->select('timestamp', 'open', 'high', 'low', 'close', 'trading_crypto', 'trading_fiat', 'volume')
                    ->take(@$_GET['limit'])
                    ->get();

            } else {
                $getPrices = CryptoPrice::orderBy('million_time', 'desc')->where('million_time', '<=', $_GET['startTime'])->limit(60)->get(['timestamp', 'open', 'high', 'low', 'close', 'trading_crypto', 'trading_fiat', 'volume']);
            }

        } else {
            if (@$_GET['limit']) {

                $totalCount = CryptoPrice::count();
                $offset = max(0, $totalCount-@$_GET['limit']);
                $getPrices = CryptoPrice::orderBy('id', 'asc')
                    ->skip($offset)
                    ->select('timestamp', 'open', 'high', 'low', 'close', 'trading_crypto', 'trading_fiat', 'volume')
                    ->take(@$_GET['limit'])->get();

            } else {
                $getPrices = CryptoPrice::orderBy('million_time', 'desc')->limit(60)->get(['timestamp', 'open', 'high', 'low', 'close', 'trading_crypto', 'trading_fiat', 'volume']);
            }
        }

        $formattedData = [];
        foreach ($getPrices as $price) {
            $formattedData[] = [
                $price['timestamp'],
                $price['open'],
                $price['high'],
                $price['low'],
                $price['close'],
                $price['trading_crypto'],
                $price['trading_fiat'],
                $price['volume'],
            ];
        }

        return response()->json([
            'data' => $formattedData,
            'message' => 'Success',
        ], 200);
    }
    public function getStreak()
    {
        $streak = getStreakAmount();
        return response()->json([
            'data' => number_format($streak, 2),
            'message' => 'Success',
        ], 200);

    }
    public function getOpenTradeCount()
    {
        $tradings = UserTrading::where('user_id', auth()->user()->id)->where('status', 'open')->count();
        return response()->json([
            'items' => $tradings,
            'message' => 'Success',
        ],
            200);
    }
}
