<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserMining;
use App\Models\Wallet;
use App\Models\WalletMining;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckUserMining extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-user-mining';

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
        $userMinings = UserMining::where('status', 'Active')
            ->whereDate('active_date', '<=', Carbon::now())
            ->whereDate('expire_date', '>=', Carbon::now())
            ->get();

        $trx = Str::uuid();
        foreach ($userMinings as $item) {
            $chkWalletMining = WalletMining::where('user_id', $item->user_id)->where('user_mining_id', $item->id)->whereDate('created_at', Carbon::now())->first();
            if (!@$chkWalletMining) {
                $netProfit = $item->daily_profit - ($item->daily_profit * $item->service_fee / 100);
                $walletMining = new WalletMining();
                $walletMining->user_id = $item->user_id;
                $walletMining->trx = $trx;
                $walletMining->user_mining_id = $item->id;
                $walletMining->amount = $netProfit;
                $walletMining->type = 'In';
                $walletMining->service_value = $item->service_fee;
                $walletMining->service_fee = $item->daily_profit * $item->service_fee / 100;
                $walletMining->value = $item->daily_profit;
                $walletMining->description = 'Daily Mining Profit';
                $walletMining->save();

                $wallet = new Wallet();
                $wallet->user_id = $item->user_id;
                $wallet->trx = $trx;
                $wallet->amount = $walletMining->amount;
                $wallet->description = 'Daily Mining Profit';
                $wallet->type = 'In';
                $wallet->save();

                //send email
                //send email to notification that get bonus
                $userManager = User::find($item->user_id);
                $email = $userManager->email;
                $name = $userManager->first_name;
                $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
            Segoe, sans-serif; mso-line-height-alt: 14px;">
            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                <b><strong>Hello! ' . @$name . '</strong></b></p>
            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                &nbsp;
            </p>
            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                Congratulations you already received daily transaction for mining commission<br>
            </p><br>

            <table width = "328" border = "0">
                <tbody>
                    <tr>
                        <td width = "114">Amount</td>
                        <td width = "15" align = "center">:</td>
                        <td width = "185">$' . number_format($wallet->amount, 2) . '</td>
                    </tr>

                    <tr>
                        <td>Mining Amount</td>
                        <td align = "center">:</td>
                        <td>$' . number_format($item->price, 2) . '</td>
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
                \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Mining Commission - 7Trade"));
            }

        }

    }
}
