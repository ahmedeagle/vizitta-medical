<?php


namespace App\Listeners;

use App\Events\OfferWasVisited;
use App\Events\OfferWasVisitedV2;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;

class UpdateTheOfferCounterV2
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param OfferWasVisitedV2 $event
     * @return bool|void
     */
    public function handle(OfferWasVisitedV2 $event)
    {
        return $this->updateCounter($event->offer);
    }

    /**
     * @param $post
     */
    public function updateCounter($offer)
    {
        $offer->visits = $offer->visits + 1;
        $offer->save();
     }
}
