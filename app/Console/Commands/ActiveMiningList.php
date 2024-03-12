<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\UserMining;
use Illuminate\Console\Command;

class ActiveMiningList extends Command
{
      /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:active-mining-list';

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
        $currentDate = Carbon::now();
        $active_date = $currentDate->addDays(1);
        $miningLists = UserMining::where('status','Processing')->get();
        foreach ($miningLists as $key) {
            $key->status      = 'Active';
            $key->active_date = $active_date;
            $key->save();
        }
    }
}
