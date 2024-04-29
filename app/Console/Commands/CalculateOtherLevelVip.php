<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Models\Generation;
use App\Models\User;
use App\Models\UserTrading;
use App\Models\Vip;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateOtherLevelVip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-other-level-vip';

    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $yesterday = $today->subDay();

        $referralTradings = UserTrading::whereDate('created_at', $yesterday)
            ->where('status', 'Closed')
            ->where('is_commission_level', 0)
            ->get();

        foreach ($referralTradings as $userTrading) {
            $trx = $userTrading->trx;
            $generations = Generation::where('user_id', $userTrading->user_id)->get();

            foreach ($generations as $generation) {
                $user = User::find($generation->user_id_generation);

                if ($user->vip_id > 1) {
                    $vip = Vip::find($user->vip_id);
                    if ($vip->level <= $generation->type) {
                        $commissionVolume = 0;

                        switch ($vip->level) {
                            case 2:
                                $commissionVolume = $vip->f2;
                                break;
                            case 3:
                                $commissionVolume = $vip->f3;
                                break;
                            case 4:
                                $commissionVolume = $vip->f4;
                                break;
                            case 5:
                                $commissionVolume = $vip->f5;
                                break;
                            case 6:
                                $commissionVolume = $vip->f6;
                                break;
                            case 7:
                                $commissionVolume = $vip->f7;
                                break;
                            default:
                                break;
                        }

                        if ($commissionVolume > 0) {
                            $commissionAmount = $userTrading->amount * ($commissionVolume / 100);

                            $commission = new Commission();
                            $commission->trx = $trx;
                            $commission->user_id = $user->id;
                            $commission->trading_id = $userTrading->id;
                            $commission->amount = $commissionAmount;
                            $commission->value = $commissionVolume;
                            $commission->volume = $userTrading->amount;
                            $commission->type = 'Vip Trading Commission';
                            $commission->description = 'Trading Commission From Downline as Vip Level ' . $vip->level;
                            $commission->save();

                            $wallet = new Wallet();
                            $wallet->trx = $trx;
                            $wallet->user_id = $user->id;
                            $wallet->amount = $commissionAmount;
                            $wallet->description = 'Transfer from Commission level ' . $vip->level;
                            $wallet->type = 'In';
                            $wallet->save();
                            //send email to notification that get bonus
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
            <td width = "185">$' . number_format($commissionAmount, 2) . '</td>
        </tr>

        <tr>
            <td>Period</td>
            <td align = "center">:</td>
            <td>' . $yesterday . '</td>
        </tr>
        <tr>
            <td>Trading Volume</td>
            <td align = "center">:</td>
            <td>$' . $commission->volume . '</td>
        </tr>
        <tr>
            <td>Value Commission</td>
            <td align = "center">:</td>
            <td>' . $commissionVolume . '%</td>
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

            $userTrading->is_commission_level = 1;
            $userTrading->save();
        }
    }

}
