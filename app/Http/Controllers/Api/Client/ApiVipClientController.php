<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Api\Client\ApiVipClientController;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\User;
use App\Models\UserTrading;
use App\Models\Vip;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiVipClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $getVip = User::with('Vip')->find(auth()->user()->id);
        $myReferrals = User::where('referral_id', auth()->user()->id)->count();
        $myReferralsVip = User::where('referral_id', auth()->user()->id)->where('is_vip', '>', 0)->count();
        $tradingCommission = Commission::where('user_id', auth()->user()->id)->where('type', 'Trading Commission')->sum('amount');
        $checkReferral = User::where('referral_id', auth()->user()->id)->pluck('id');

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $today = Carbon::today();
        $referralVol = UserTrading::whereIn('user_id', $checkReferral)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('status', 'Closed')
            ->sum('amount');

        return response()->json([
            'vip' => $getVip,
            'referral' => $myReferrals,
            'referralVip' => $myReferralsVip,
            'referralVol' => number_format($referralVol, 0),
            'tradingCommission' => number_format($tradingCommission, 2),
            'message' => 'Success VIP Detail',
        ],
            200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getVip()
    {
        $getUser = User::with(['Vip'])->find(auth()->user()->id);
        $default = Vip::where('id', '>', auth()->user()->vip_id)->orderBy('id', 'asc')->first();
        $getVip = Vip::where('id', '>', auth()->user()->vip_id)->get();
        $myReferrals = User::where('referral_id', auth()->user()->id)->get();
        return response()->json([
            'default' => $default,
            'user' => $getUser,
            'vip' => $getVip,
            'referrals' => $myReferrals,
            'message' => 'Success',
        ],
            200);
    }
    public function getReferrals()
    {

        $referrals = User::with('Vip')
            ->where('referral_id', auth()->user()->id)
            ->get();

        foreach ($referrals as $referral) {
            if (strtotime(@$_GET['date']) !== false) {
                $startDate = Carbon::parse($_GET['date']);
                $endDate = Carbon::parse($_GET['date2']);
                $volumeTradings = UserTrading::where('user_id', $referral->id)
                    ->where('is_commission', 'Yes')
                    ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                    ->get();

            } else {
                $volumeTradings = UserTrading::where('user_id', $referral->id)
                    ->where('is_commission', 'Yes')
                    ->get();
            }

            $totalVolumeTradingAmount = $volumeTradings->sum('amount'); // Assuming 'amount' is the attribute representing volume trading amount
            $totalCommission = $volumeTradings->sum('referral_bonus'); // Assuming 'amount' is the attribute representing volume trading amount
            $referral->totalVolumeTradingAmount = $totalVolumeTradingAmount;
            $referral->totalCommission = $totalCommission;
        }

        // Return JSON response with referrals and volume trading data
        return response()->json([
            'referrals' => $referrals,
            'message' => 'Success',
        ], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'termsChecked' => 'required|accepted',
            'readChecked' => 'required|accepted',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 201);
        }
        if (auth()->user()->google2fa_enable != 'Yes') {
            return response()->json(['message' => '2FA Authenticator is disable', 'error' => $request->termsChecked], 201);
        }

        if (walletBalance(auth()->user()->id) < 100) {
            return response()->json(['message' => 'Invalid input', 'error' => 'Insufficient balance'], 201);
        }

        $google2fa = app('pragmarx.google2fa');
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if (!$oneCode) {
            return response()->json([
                'message' => 'Invalid 2FA Code'],
                201);
        }
        $trx = Str::uuid();
        $wallet = new Wallet();
        $wallet->user_id = auth()->user()->id;
        $wallet->trx = @$trx;
        $wallet->amount = 100;
        $wallet->description = 'Register VIP';
        $wallet->memo = $request->input('memo');
        $wallet->type = 'Out';
        $wallet->save();

        $user = User::with(['Referral'])->find(auth()->user()->id);
        $user->is_vip = 1;
        $user->save();

        $sendAmount = 100;
        $created_at = $wallet->created_at;
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
            Congratulations your VIP has been active.<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td>Registered Fee</td>
                    <td align = "center">:</td>
                    <td>' . $sendAmount . ' USD</td>
                </tr>
                <tr>
                    <td>VIP Level</td>
                    <td align = "center">:</td>
                    <td>Level 1</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                                     : </td>
                    <td>' . \Carbon\Carbon::parse($created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Register VIP"));

        /** Get Referral Amount for VIP Commission */
        if (@$user->referral_id and @$user->Referral->is_vip == 1) {
            $referral_amount = 100 * 0.2;
            $walletReferral = new Wallet();
            $walletReferral->user_id = @$user->referral_id;
            $walletReferral->trx = @$trx;
            $walletReferral->amount = $referral_amount;
            $walletReferral->type = 'In';
            $walletReferral->description = 'Register VIP Referral';
            $walletReferral->save();

            $walletReferral = $wallet->created_at;
            $email = $user->Referral->email;
            $name = $user->Referral->first_name;

            $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            Congratulations you get commission from VIP registration.<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td>Registered Fee</td>
                    <td align = "center">:</td>
                    <td>' . $referral_amount . ' USD</td>
                </tr>
                <tr>
                    <td>Value</td>
                    <td align = "center">:</td>
                    <td>20% of Registration Fee</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                                     : </td>
                    <td>' . \Carbon\Carbon::parse($created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
            \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "VIP Commission"));

        }

        return response()->json(['message' => 'VIP updated successfully'], 200);
    }

    public function upgradeVip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'termsChecked' => 'required|accepted',
            'code' => 'required|string',
            'vipId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 201);
        }
        if (auth()->user()->google2fa_enable != 'Yes') {
            return response()->json(['message' => '2FA Authenticator is disable', 'error' => $request->termsChecked], 201);
        }

        $vip = Vip::find($request->vipId);

        if (!$vip) {
            return response()->json(['message' => 'Invalid input', 'error' => 'Vip not found'], 201);
        }

        if (walletBalance(auth()->user()->id) < $vip->price) {
            return response()->json(['message' => 'Insufficient balance', 'error' => 'Insufficient balance'], 201);
        }

        $google2fa = app('pragmarx.google2fa');
        $oneCode = $google2fa->verifyKey(auth()->user()->google2fa_secret, $request->code);
        if (!$oneCode) {
            return response()->json([
                'message' => 'Invalid 2FA Code'],
                201);
        }
        $trx = Str::uuid();
        $wallet = new Wallet();
        $wallet->user_id = auth()->user()->id;
        $wallet->trx = @$trx;
        $wallet->amount = $vip->price;
        $wallet->description = 'Upgrade VIP to ' . $vip->name;
        $wallet->memo = $request->input('memo');
        $wallet->type = 'Out';
        $wallet->save();

        $user = User::with(['Referral'])->find(auth()->user()->id);
        $user->vip_id = $vip->id;
        $user->save();

        $sendAmount = $vip->price;
        $created_at = $wallet->created_at;
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
            Congratulations your VIP has been upgrade.<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td>Upgrade Fee</td>
                    <td align = "center">:</td>
                    <td>' . $sendAmount . ' USD</td>
                </tr>
                <tr>
                    <td>VIP Level</td>
                    <td align = "center">:</td>
                    <td>' . $vip->name . '</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                                     : </td>
                    <td>' . \Carbon\Carbon::parse($created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
        \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Upgrade VIP"));

        return response()->json(['message' => 'VIP updated successfully'], 200);
    }
    public function getCommission()
    {
        $commissions = Commission::where('user_id', auth()->user()->id)->get();
        return response()->json(['message' => 'Commission Items', 'items' => $commissions], 200);
    }
}
