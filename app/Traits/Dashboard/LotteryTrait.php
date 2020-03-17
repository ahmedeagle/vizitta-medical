<?php

namespace App\Traits\Dashboard;

use App\Models\Provider;
use Freshbitsweb\Laratables\Laratables;
use DB;

trait LotteryTrait
{


    public function getLotteryBranches()
    {
        return Laratables::recordsOf(Provider::class, function ($query) {
            return $query->whereNull('provider_id') ->where('lottery',1) ;
        });
    }
}
