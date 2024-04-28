<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Models\User;
use App\Models\UserTrading;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckManagerCommission extends Command
{
      /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-manager-commission';

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
        $userTrading = UserTrading::where('is_manager_count', 0)->where('status', 'closed')->where('is_profit', 'No')->get();
        foreach ($userTrading as $key => $value) {
            $trx       = $value->trx;
            $amountCom = $value->amount * 0.2;
            $user      = User::find($value->user_id);
            if ($user->manager_id > 0) {
                $commissions              = new Commission();
                $commissions->trx         = $trx;
                $commissions->user_id     = $user->manager_id;
                $commissions->trading_id  = $value->id;
                $commissions->amount      = $amountCom;
                $commissions->value       = 0.2;
                $commissions->volume      = $value->amount;
                $commissions->type        = 'Manager Trading Commission';
                $commissions->description = 'Trading Commission From Downline';
                $commissions->save();

                $wallet              = new Wallet();
                $wallet->trx         = $trx;
                $wallet->user_id     = $user->manager_id;
                $wallet->amount      = $amountCom;
                $wallet->description = 'Transfer from Commission';
                $wallet->type        = 'In';
                $wallet->save();

                  //send email to notification that get bonus
                $userManager = User::find($user->manager_id);
                $email       = $userManager->email;
                $name        = $userManager->first_name;
                $content     = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
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
                        <td width = "185">$' . number_format($amountCom, 2) . '</td>
                    </tr>

                    <tr>
                        <td>Trading Amount</td>
                        <td align = "center">:</td>
                        <td>$' . number_format($value->amount, 2) . '</td>
                    </tr>
                    <tr>
                        <td>Value Commission</td>
                        <td align = "center">:</td>
                        <td>20%</td>
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
                  // \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Manager Commission Received"));
            }
            \DB::table('user_tradings')->where('id', $value->id)->update(['is_manager_count' => 1]);
        }

    }
}
