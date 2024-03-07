<?php

namespace App\Http\Controllers\Api\Client\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\Exchange;
use App\Models\User;
use App\Models\UserCrypto;
use App\Models\Wallet;
use App\Models\WalletTrading;
use App\Models\Withdraw;
use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiWalletClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (@$_GET['limit']) {
            $wallets = Wallet::where('user_id', auth()->user()->id)->orderby('id', 'desc')->paginate(@$_GET['limit']);
            return response()->json([
                'items' => $wallets,
                'message' => 'Success',
            ],
                200);
        } else {
            $wallets = Wallet::where('user_id', auth()->user()->id)->orderby('id', 'desc')->paginate(10);
            return response()->json([
                'items' => $wallets,
                'message' => 'Success',
            ],
                200);
        }

    }

    public function createDepositAddress(Request $request)
    {
        $merchant_transaction_id = IdGenerator::generate(['table' => 'user_cryptos', 'field' => 'merchant_transaction_id', 'length' => 9, 'prefix' => 'MRC']);
        $coin = 'USDT';
        $network = 'bsc-bep20';
        $crypto = AlphaCreateAddress($coin, $network, $merchant_transaction_id);
        // dd($crypto);
        $userCrypto = new UserCrypto();
        $userCrypto->user_id = auth()->user()->id;
        $userCrypto->crypto_id = 1;
        $userCrypto->crypto_name = 'USDT';
        $userCrypto->crypto_code = 'USDT BEP20';
        $userCrypto->crypto_address = $crypto['address'];
        $userCrypto->merchant_transaction_id = $merchant_transaction_id;
        $userCrypto->save();

        return response()->json([
            'message' => 'Address Created'],
            201);
    }
    public function getDepositAddress()
    {
        $userCrypto = UserCrypto::where('user_id', auth()->user()->id)->orderBy('id', 'desc')->first();

        if (!$userCrypto) {
            return response()->json([
                'message' => 'No Address Found'],
                201);
        }
        return response()->json([
            'crypto' => $userCrypto,
            'message' => 'Password has been updated',
        ],
            200);
    }
    public function getBalance()
    {
        $userCryptoIn = Wallet::where('user_id', auth()->user()->id)->where('type', 'In')->sum('amount');
        $userCryptoOut = Wallet::where('user_id', auth()->user()->id)->where('type', 'Out')->sum('amount');
        $balance = $userCryptoIn - $userCryptoOut;

        return response()->json([
            'value' => $balance,
            'message' => 'Balance',
        ],
            200);
    }
    public function getBalanceAsset()
    {
        $balance = walletBalance(auth()->user()->id) + WalletTradingBalance(auth()->user()->id);
        if (!$balance) {
            $bl = 0;
        } else {
            $bl = $balance;
        }
        return response()->json([
            'value' => $bl,
            'message' => 'Balance',
        ],
            200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function postWithdraw(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'address' => 'required|string',
            'code' => 'required|string',
            'memo' => 'nullable|string',
        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'code.required' => 'The 2FA field is required.',
            'address.required' => 'The address field is required.',
            'address.string' => 'The address must be a string.',
        ]);
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 201);
        }
        if (auth()->user()->google2fa_enable != 'Yes') {
            return response()->json(['message' => '2FA Authenticator is disable'], 201);
        }

        if (walletBalance(auth()->user()->id) < $request->amount) {
            return response()->json(['message' => 'Invalid input', 'error' => 'Insufficient balance'], 201);
        }

        $google2fa = app('pragmarx.google2fa');
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if (!$oneCode) {
            return response()->json([
                'message' => 'Invalid 2FA Code'],
                201);
        }

        // Create a new withdrawal record
        $fee = (2.5 / 100) * $request->amount;
        $amountToTransfer = $request->amount - $fee;

        $coin = 'USDT';
        $network = 'bsc-bep20';
        $address = $request->address;
        $amount = $amountToTransfer;
        $orderId = IdGenerator::generate(['table' => 'user_cryptos', 'field' => 'merchant_transaction_id', 'length' => 9, 'prefix' => 'MRC']);
        $receiverId = auth()->user()->id;
        $wd = AlphaCreateWd($coin, $network, $address, $amount, $orderId, $receiverId);
        if ($wd->success == false) {
            return response()->json([
                'withdraw' => $wd,
                'message' => $wd->msg],
                201);
        }
        $crypto = Cryptocurrency::where('id', 1)->first();

        $walletSender = new Wallet();
        $walletSender->user_id = auth()->user()->id;
        $walletSender->trx = @$wd->txId;
        $walletSender->amount = $request->amount;
        $walletSender->description = 'Withdraw';
        $walletSender->memo = $request->input('memo');
        $walletSender->type = 'Out';
        $walletSender->save();

        $withdraw = new Withdraw();
        $withdraw->user_id = auth()->user()->id;
        $withdraw->txid = @$wd->txId;
        $withdraw->transaction_code = $orderId;
        $withdraw->status = @$wd->status;
        $withdraw->type = 'Crypto';
        $withdraw->description = $request->input('memo');
        $withdraw->amount = $request->input('amount');
        $withdraw->amount_totransfer = $amountToTransfer;
        $withdraw->transfer_fee = $fee;
        $withdraw->crypto_transaction_id = $crypto->id;
        $withdraw->crypto_name = $crypto->crypto_name;
        $withdraw->crypto_address = $request->input('address');
        $withdraw->crypto_amount = $request->input('amount');
        $withdraw->crypto_network = $crypto->crypto_name;
        $withdraw->crypto_date = now();
        $withdraw->crypto_amount_totransfer = $amountToTransfer;
        $withdraw->explorer_url = $request->input('explorer_url');
        $withdraw->transaction_fees = $fee;
        $withdraw->processing_fees = $fee;
        $withdraw->crypto_fees = $fee;
        $withdraw->save();

        $sendAmount = $request->amount;
        $cost = $fee;
        $receiveAmount = $request->amount;
        $created_at = $withdraw->created_at;
        $email = auth()->user()->email;
        $name = auth()->user()->first_name;

        $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            Congratulations your withdraw has been processed.<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td>Wallet Amount</td>
                    <td align = "center">:</td>
                    <td>' . $sendAmount . ' USD</td>
                </tr>
                <tr>
                    <td>Transaction Fee</td>
                    <td align = "center">:</td>
                    <td>' . $cost . ' USD</td>
                </tr>
                <tr>
                    <td>Receive Amount</td>
                    <td align = "center">:</td>
                    <td>' . $receiveAmount . ' USD</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                             : </td>
                    <td>' . Carbon::parse($created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Withdraw"));

        return response()->json(['message' => 'Withdrawal successful'], 200);
    }
    public function getTradingBalance()
    {
        $balance = WalletTradingBalance(auth()->user()->id);
        if ($balance == null) {
            $bl = 0;
        } else {
            $bl = $balance;
        }
        return response()->json([
            'value' => $bl,
            'message' => 'Balance',
        ],
            200);
    }
    public function transferPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.1',
            'email' => 'required|string|email',
            'code' => 'required|string',
            'memo' => 'nullable|string',
        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'code.required' => 'The 2FA field is required.',
            'email.required' => 'The email field is required.',

        ]);
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input', 'error' => $validator->errors()], 201);
        }

        if (walletBalance(auth()->user()->id) < $request->amount) {
            return response()->json(['message' => 'Invalid input', 'error' => 'Insufficient balance'], 201);
        }

        $receiver = User::where('email', $request->email)->where('email', '!=', auth()->user()->email)->first();
        if (!$receiver) {
            return response()->json(['message' => 'Receiver email not found'], 201);
        }
        if (auth()->user()->google2fa_enable != 'Yes') {
            return response()->json(['message' => '2FA Authenticator is disable'], 201);
        }
        $google2fa = app('pragmarx.google2fa');
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if (!$oneCode) {
            return response()->json(['message' => 'Invalid 2FA Code'], 201);
        }

        $wallet = new Wallet();
        $wallet->user_id = $receiver->id;
        $wallet->trx = Str::uuid();
        $wallet->amount = $request->amount;
        $wallet->description = 'Transfer from ' . auth()->user()->email;
        $wallet->memo = $request->input('memo');
        $wallet->type = 'In';
        $wallet->save();

        $walletSender = new Wallet();
        $walletSender->user_id = auth()->user()->id;
        $walletSender->trx = $wallet->trx;
        $walletSender->amount = $request->amount;
        $walletSender->description = 'Transfer to ' . $receiver->email;
        $walletSender->memo = $request->input('memo');
        $walletSender->type = 'Out';
        $walletSender->save();

        $senderName = auth()->user()->first_name;
        $senderEmail = auth()->user()->email;
        $sendAmount = $request->amount;
        $cost = 0;
        $receiveAmount = $request->amount;
        $created_at = $wallet->created_at;
        $email = $receiver->email;
        $name = $receiver->firstname;

        $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            Congratulations you already received Wallet transaction<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td width = "114">Sender Name</td>
                    <td width = "15" align = "center">:</td>
                    <td width = "185">' . $senderName . '</td>
                </tr>
                <tr>
                    <td width = "114">Sender Email</td>
                    <td width = "15" align = "center">:</td>
                    <td width = "185">' . $senderEmail . '</td>
                </tr>
                <tr>
                    <td>Wallet Amount</td>
                    <td align = "center">:</td>
                    <td>' . $sendAmount . ' USD</td>
                </tr>
                <tr>
                    <td>Transaction Fee</td>
                    <td align = "center">:</td>
                    <td>' . $cost . ' USD</td>
                </tr>
                <tr>
                    <td>Receive Amount</td>
                    <td align = "center">:</td>
                    <td>' . $receiveAmount . ' USD</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                             : </td>
                    <td>' . Carbon::parse($created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Received Wallet Transaction"));

        return response()->json(['message' => 'Transferred successfully'], 200);
    }
    public function getHistories()
    {

        if (@$_GET['limit']) {
            $wallets = Exchange::where('user_id', auth()->user()->id)->orderby('id', 'desc')->paginate($_GET['limit']);
            return response()->json([
                'items' => $wallets,
                'limit' => @$_GET['limit'],
                'message' => 'Success',
            ],
                200);
        } else {
            $wallets = Exchange::where('user_id', auth()->user()->id)->orderby('id', 'desc')->paginate(10);
            return response()->json([
                'items' => $wallets,
                'message' => 'Success',
            ],
                200);
        }

    }

    public function exchangeToWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',

        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be more than 0.9',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input', 'error' => $validator->errors()], 201);
        }

        if (WalletTradingBalance(auth()->user()->id) < $request->amount) {
            return response()->json(['message' => 'Invalid input', 'error' => 'Insufficient balance'], 201);
        }

        $trx = Str::uuid();
        $exchange = new WalletTrading();
        $exchange->trx = $trx;
        $exchange->user_id = auth()->user()->id;
        $exchange->type = 'Out';
        $exchange->amount = $request->amount;
        $exchange->description = 'Exchange to wallet';
        $exchange->save();

        $history = new Exchange();
        $history->trx = $trx;
        $history->user_id = auth()->user()->id;
        $history->amount = $request->amount;
        $history->type = 'Transfer out';
        $history->status = 'Accept';
        $history->description = 'Transfer to Wallet account';
        $history->save();

        $wallet = new Wallet();
        $wallet->trx = $trx;
        $wallet->user_id = auth()->user()->id;
        $wallet->amount = $request->amount;
        $wallet->description = 'Transfer from Live Account';
        $wallet->type = 'In';
        $wallet->save();

        return response()->json(['message' => 'Transferred successfully'], 200);
    }
    public function exchangeToLive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',

        ], [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be more than 0.9',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input', 'error' => $validator->errors()], 201);
        }

        if (walletBalance(auth()->user()->id) < $request->amount) {
            return response()->json(['message' => 'Insufficient balance', 'error' => 'Insufficient balance'], 201);
        }

        $trx = Str::uuid();

        $exchange = new WalletTrading();
        $exchange->trx = $trx;
        $exchange->user_id = auth()->user()->id;
        $exchange->type = 'In';
        $exchange->amount = $request->amount;
        $exchange->description = 'From Wallet Exchange';
        $exchange->save();

        $history = new Exchange();
        $history->trx = $trx;
        $history->user_id = auth()->user()->id;
        $history->amount = $request->amount;
        $history->type = 'Transfer In';
        $history->status = 'Accept';
        $history->description = 'Transfer from Wallet account';
        $history->save();

        $wallet = new Wallet();
        $wallet->trx = $trx;
        $wallet->user_id = auth()->user()->id;
        $wallet->amount = $request->amount;
        $wallet->description = 'Transfer to Live Account';
        $wallet->type = 'Out';
        $wallet->save();

        return response()->json(['message' => 'Transferred successfully'], 200);
    }
}
