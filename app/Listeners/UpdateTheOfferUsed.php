<?php


namespace App\Listeners;

use App\Events\OfferWasUsed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateTheOfferUsed
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
     * @param OfferWasUsed $event
     * @return bool|void
     */
    public function handle(OfferWasUsed $event)
    {
        return $this->updateUsedNumber($event->offer);
    }

    /**
     * @param $post
     */
    public function updateUsedNumber($offer)
    {
        $offer->uses = $offer->uses + 1;
        $offer->save();
    }
}
