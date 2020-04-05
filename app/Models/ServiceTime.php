<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ServiceTime extends Model
{
    protected $table = 'service_times';
    public $timestamps = true;

    protected $fillable = ['day_name', 'day_code', 'from_time', 'to_time', 'service_id', 'reservation_period', 'provider_id', 'branch_id', 'order'];

    protected $forcedNullStrings = ['reservation_period'];
    protected $hidden = ['service_id', 'updated_at', 'created_at', 'provider_id', 'reservation_period', 'order'];
    protected $appends = ['time_duration'];

    public function getDayNameAttribute($value)
    {
        return trans('messages.' . $value);
    }


    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";

        return parent::setAttribute($key, $value);
    }


    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }


    public function getReservationPeriodAttribute($value)
    {
        return $value != null ? $value : 0;
    }

    public function getTimeDurationAttribute()
    {
        return $this->reservation_period;
    }

}
