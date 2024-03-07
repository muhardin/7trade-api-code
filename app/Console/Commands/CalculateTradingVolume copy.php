<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Models\User;
use App\Models\UserTrading;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CalculateTradingVolume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-trading-volume-copy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $vips = User::with('Vip')->where('is_vip', 1)->get();
        foreach ($vips as $vip) {
            // $checkReferral = User::where('referral_id', $vip->id)->pluck('id');
            // $referralVol   = UserTrading::whereIn('user_id', $checkReferral)
            // ->whereDate('created_at', $today)
            // ->where('status', 'Closed')
            // ->sum('amount');

            // dd($referralVol);
            // $vip->referral_volume = $referralVol;
            $referralLists = User::where('referral_id', $vip->id)->get();
            foreach ($referralLists as $referral) {
                $referralVol = UserTrading::where('user_id', $referral->id)
                    ->whereDate('created_at', $today)
                    ->where('status', 'Closed')
                    ->where('is_commission', 'No')
                    ->sum('amount');

                if (@$referralVol > 0) {

                    $amount = ($vip->Vip->commission / 100) * $referralVol;

                    $trx = Str::uuid();
                    $commissions = new Commission();
                    $commissions->trx = $trx;
                    $commissions->user_id = $vip->id;
                    $commissions->amount = $amount;
                    $commissions->type = 'Trading Commission';
                    $commissions->description = 'Trading Commission From Referral ' . $referral->first_name;
                    $commissions->save();

                    $referralTrading = UserTrading::where('user_id', $referral->id)
                        ->whereDate('created_at', $today)
                        ->where('status', 'Closed')
                        ->where('is_commission', 'No')
                        ->get();

                    foreach ($referralTrading as $trading) {
                        $trading->is_commission = 'Yes';
                        $trading->referral_bonus = ($vip->Vip->commission / 100) * $trading->amount;
                        $trading->save();
                    }

                    $wallet = new Wallet();
                    $wallet->trx = $trx;
                    $wallet->user_id = $vip->id;
                    $wallet->amount = $amount;
                    $wallet->description = 'Transfer from Commission';
                    $wallet->type = 'In';
                    $wallet->save();

                    //send email to notif that get bonus
                    $email = $vip->email;
                    $name = $vip->first_name;
                    $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
        Segoe, sans-serif; mso-line-height-alt: 14px;">
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            <b><strong>Hello! ' . @$name . '</strong></b></p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;
        </p>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            Congratulations you already received Wallet transaction for trading commission of your referral.<br>
        </p><br>

        <table width = "328" border = "0">
            <tbody>
                <tr>
                    <td width = "114">Amount</td>
                    <td width = "15" align = "center">:</td>
                    <td width = "185">$' . number_format($amount, 2) . '</td>
                </tr>

                <tr>
                    <td>Trader Name</td>
                    <td align = "center">:</td>
                    <td>' . $referral->first_name . '</td>
                </tr>
                <tr>
                    <td>Trading Volume</td>
                    <td align = "center">:</td>
                    <td>$' . number_format($referralVol, 2) . '</td>
                </tr>
                <tr>
                    <td>Value Commission</td>
                    <td align = "center">:</td>
                    <td>' . $vip->Vip->commission . ' %</td>
                </tr>
                <tr>
                    <td>Date/Time</td>
                    <td  align = "center">                                     : </td>
                    <td>' . Carbon::parse($wallet->created_at)->format('Y-m-d H: i: s') . '</td>
                </tr>
            </tbody>
        </table>
        <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
            &nbsp;</p>
        </p>
    </div>';
                    \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Commission Received"));

                }

            }

        }

    }
}