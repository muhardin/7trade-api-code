<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Wallet;
use App\Models\UserMining;
use Illuminate\Support\Str;
use App\Models\WalletMining;
use Illuminate\Console\Command;

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
        $userMinings = UserMining::where('status','Active')
        ->whereDate('active_date','<=',Carbon::now())
        ->whereDate('expire_date','>=',Carbon::now())
        ->get();
        
        $trx         = Str::uuid();
        foreach ($userMinings as $item) {  
            $chkWalletMining = WalletMining::where('user_id',$item->user_id)->whereDate('created_at',Carbon::now())->first();
            if(!@$chkWalletMining){
                $walletMining                 = new WalletMining();
                $walletMining->user_id        = $item->user_id;
                $walletMining->trx            = $trx;
                $walletMining->user_mining_id = $item->id;
                $walletMining->amount         = $item->daily_profit;
                $walletMining->type           = 'In';
                $walletMining->description    = 'Daily Mining Profit';
                $walletMining->save();

                $wallet              = new Wallet();
                $wallet->user_id     = $item->user_id;
                $wallet->trx         = $trx;
                $wallet->amount      = $item->daily_profit;
                $wallet->description = 'Daily Mining Profit';
                $wallet->type        = 'In';
                $wallet->save();

                //send email
            }

        }

    }
}
