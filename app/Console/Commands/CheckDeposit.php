<?php

namespace App\Console\Commands;

use App\Models\UserCrypto;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDeposit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-deposit';

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
        $userCryptos = UserCrypto::with('User')->get();
        foreach ($userCryptos as $crypto) {
            $coin = $crypto->crypto_name;
            $network = $crypto->crypto_network;
            $address = $crypto->crypto_address;

            // $coin = 'USDT';
            // $network = 'bsc-bep20';
            // $address = '0x8b17f43addd1151c0d5a88961a59d6b6d9d18864';

            $checkDeposit = AlphaCheckAddress($coin, $network, $address);
            // dd($checkDeposit);
            if (@$checkDeposit->success == true) {
                $transactions = $checkDeposit->transactions;
                foreach ($transactions as $key) {
                    if ($key->status == 'completed') {
                        $checkWallet = Wallet::where('trx', $key->hash)->first();
                        if (!@$checkWallet) {
                            $wallet = new Wallet();
                            $wallet->user_id = $crypto->user_id;
                            $wallet->trx = $key->hash;
                            $wallet->amount = $key->amount;
                            $wallet->type = 'In';
                            $wallet->confirmation_code = $key->processorTransactionId;
                            $wallet->description = 'Deposit from your deposit address';
                            $wallet->save();

                            $sendAmount = $wallet->amount;
                            $created_at = $wallet->created_at;
                            $email = $crypto->User->email;
                            $name = $crypto->User->first_name;

                            $content = '<div style="font-size: 12px; line-height: 1.2; color: #555555; font-family: " Lato", Tahoma, Verdana,
                            Segoe, sans-serif; mso-line-height-alt: 14px;">
                            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                                <b><strong>Hello! ' . @$name . '</strong></b></p>
                            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                                &nbsp;
                            </p>
                            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                                Congratulations your deposit has completed.<br>
                            </p><br>

                            <table width = "328" border = "0">
                                <tbody>
                                    <tr>
                                        <td>Amount</td>
                                        <td align = "center">:</td>
                                        <td>' . $sendAmount . ' USDT</td>
                                    </tr>
                                    <tr>
                                        <td>Hash</td>
                                        <td align = "center">:</td>
                                        <td>' . $wallet->trx . '</td>
                                    </tr>
                                    <tr>
                                        <td>Crypto</td>
                                        <td align = "center">:</td>
                                        <td>USDT</td>
                                    </tr>
                                    <tr>
                                        <td>Date/Time</td>
                                        <td  align = "center"> : </td>
                                        <td>' . Carbon::parse($created_at)->format('Y-m-d H: i: s') . '</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p style = "line-height: 1.2; word-break: break-word; font-size: 16px; mso-line-height-alt: 19px; margin: 0;">
                                &nbsp;</p>
                            </p>
                        </div>';
                            \Mail::to($email)->bcc('dexgame88@gmail.com')->send(new \App\Mail\BaseMail($content, "Deposit"));
                        }
                    }

                }
            }
        }

    }
}
