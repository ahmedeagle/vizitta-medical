<?php

namespace App\Traits\CPanel;

use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;

trait OfferTrait
{

    public function getAllOffers()
    {
        return Offer::active()->valid()->select('id', 'title_' . app()->getLocale() . ' as title')->get();
    }
}
