<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\Provider;
use App\Models\Reciever;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateOfferUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $timeout = 240;

    public $offer;
    public $users;

    public function __construct(Offer $offer, $users)
    {
        $this->offer = $offer;
        $this->users = $users;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this -> offer->users()->sync($this -> users);
    }
}
