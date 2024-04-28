<?php

namespace App\Console\Commands;

use App\Models\StreakChallenge;
use App\Models\User;
use App\Models\UserStreak;
use App\Models\UserTrading;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckStreakTrade extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-streak-trade';

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
        $getStreak      = getStreakAmount();
        $streakCount    = 0;
        $streakCountNo  = 0;
        $streakCount9No = 0;

        $count9Streak    = 0;
        $streakTarget    = 5;
        $streakCountYes9 = 0;
        $description     = "Profit Streak 9x";
        $users           = User::get();
        foreach ($users as $user) {
                // Get yesterday's date
            $yesterday = Carbon::yesterday();
            $data      = UserTrading::where('user_id', $user->id)
                ->whereDate('created_at', '<=', $yesterday)
                ->where('is_streak', 0)
                ->where('amount', '>=',10)
                ->orderBy('id', 'desc')
                ->get();
            foreach ($data as $row) {
                if ($row->is_profit == 'Yes') {
                    $description = 'Profit Streak 9x';
                    $streakCount++;
                    if ($streakCount >= $streakTarget) {
                        $streakCountYes9++;
                        $streakCount = 0;
                    }
                } else {
                    $streakCount = 0;
                }

                if ($row->is_profit == 'No') {
                    $streakCountNo++;
                    $description = 'Lose Streak 9x';
                    if ($streakCountNo >= $streakTarget) {
                        $streakCount9No++;
                        $streakCountNo = 0;
                    }
                } else {
                    $streakCountNo = 0;
                }
                $setUserId = $row->user_id;

            }
                // dd($streakCountYes9 . ' | ' . $streakCount9No);
            if (@$setUserId == $user->id) {
                if ($streakCountYes9 >= 1) {
                    for ($i = 1; $i <= $streakCountYes9; $i++) {
                        $userStreak                = new UserStreak();
                        $userStreak->user_id       = $user->id;
                        $userStreak->streak_count  = $streakCountYes9;
                        $userStreak->streak_amount = $getStreak;
                        $userStreak->description   = @$description;
                        $userStreak->amount        = (0.1 / 100) * $getStreak;
                        $userStreak->save();
                    }
                    $streakCount9No = 0;
                }

                if ($streakCount9No >= 1) {
                    for ($i = 1; $i <= $streakCount9No; $i++) {
                        $userStreak                = new UserStreak();
                        $userStreak->user_id       = $user->id;
                        $userStreak->streak_count  = $streakCount9No;
                        $userStreak->streak_amount = $getStreak;
                        $userStreak->amount        = (0.1 / 100) * $getStreak;
                        $userStreak->description   = @$description;
                        $userStreak->save();
                    }
                    $streakCountYes9 = 0;
                }
            }

            UserTrading::where('user_id', $user->id)
                ->whereDate('created_at', '<=', $yesterday)->update(['is_streak' => 1]);

        }
    }
}
