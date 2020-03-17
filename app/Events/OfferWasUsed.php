<?php

namespace App\Events;

 use App\Models\PromoCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OfferWasUsed
{
    use SerializesModels;

    public $offer;

    /**
     * Create a new event instance.
     *
	 * @param PromoCode $offer
	 */
    public function __construct(PromoCode $offer)
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
