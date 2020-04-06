<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OfferTimeOld extends Model
{
    protected $table = 'offers_branches_times';
      protected $fillable = [
        'offer_id', 'branch_id', 'day_code', 'time_from', 'time_to', 'duration', 'start_from', 'end_to'
    ];
}
