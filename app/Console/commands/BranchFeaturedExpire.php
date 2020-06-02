<?php

namespace App\Console\Commands;

use App\Models\FeaturedBranch;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BranchFeaturedExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire all old subscription';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       /* $subscriptions = FeaturedBranch::where('expired', 0)->whereHas('provider')->get();
        foreach ($subscriptions as $subscription) {
            if (getDiffBetweenTwoDate($subscription->created_at, Carbon::now()) >= $subscription->duration) {
                FeaturedBranch::where('id', $subscription->id)-> delete();
            }
        }*/
    }
}
