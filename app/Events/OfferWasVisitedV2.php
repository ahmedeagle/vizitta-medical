<?php

namespace App\Events;

 use App\Models\Offer;
 use App\Models\PromoCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OfferWasVisitedV2
{
    use SerializesModels;

    public $offer;

    /**
     * Create a new event instance.
     *
	 * @param Offer $offer
	 */
    public function __construct(Offer $offer)
    {
        $this-> offer = $offer;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
