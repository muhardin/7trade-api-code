<?php

namespace App\Console\Commands;

use App\Models\UserStreak;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckMegaPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-mega-price';

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
        $megaAmount = getMegaAmount();
        $randomData = UserStreak::where('is_mega', 0)->inRandomOrder()->first();
        $txt = Str::uuid();

        if (@$megaAmount >= 0.01) {
            if (@$randomData) {
                $userStreak = new UserStreak();
                $userStreak->txt = $txt;
                $userStreak->user_id = $randomData->user_id;
                $userStreak->streak_count = $randomData->streak_count;
                $userStreak->streak_amount = $randomData->streak_amount;
                $userStreak->amount = $megaAmount;
                $userStreak->is_mega = 1;
                $userStreak->is_mega_get = 1;
                $userStreak->save();

                $wallet = new Wallet();
                $wallet->user_id = $randomData->user_id;
                $wallet->trx = $txt;
                $wallet->amount = $megaAmount;
                $wallet->description = 'Transfer Mega Win';
                $wallet->type = 'In';
                $wallet->save();

                \DB::table('user_streaks')->update(['is_mega' => 1]);
                //send email to notification
            }
        }

    }
}
