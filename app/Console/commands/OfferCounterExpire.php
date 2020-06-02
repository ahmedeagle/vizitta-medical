<?php

namespace App\Console\Commands;

use App\Models\FeaturedBranch;
use App\Models\PromoCodeCategory;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OfferCounterExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'counter:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire all old offer';


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


    }
}
