<?php


namespace App\Listeners;

use App\Events\OfferWasVisited;
 use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;

class UpdateTheOfferCounter
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
     * @param OfferWasVisited $event
     * @return bool|void
     */
    public function handle(OfferWasVisited $event)
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
