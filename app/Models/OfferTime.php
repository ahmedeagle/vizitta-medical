<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OfferTime extends Model
{
    protected $table = 'offers_branches_times';
    protected $fillable = ['offer_id', 'branch_id', 'day_name', 'day_code', 'from_time', 'to_time', 'reservation_period', 'order'];

    protected $hidden = ['offer_id', 'branch_id', 'reservation_period', 'order'];

    protected $appends = ['time_duration'];

    public function getDayNameAttribute($value)
    {
        return trans('messages.' . $value);
    }

    public function setReservationPeriodAttribute($value)
    {
        if ($value == 0 or $value == null or $value == '') {
            $this->attributes['reservation_period'] = 15;  // default 15 minutes
        } else
            $this->attributes['reservation_period'] = $value;
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'branch_id');
    }

    public function offer()
    {
        return $this->belongsTo('App\Models\Offer', 'offer_id');
    }

    public function getTimeDurationAttribute()
    {
        return $this->reservation_period;
    }
}
