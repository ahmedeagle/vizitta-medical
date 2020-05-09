<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultativeDoctorTime extends Model
{
    protected $table = 'consultative_doctor_times';
    public $timestamps = false;

    protected $fillable = ['day_name', 'day_code', 'from_time', 'to_time', 'doctor_id', 'provider_id', 'reservation_period', 'order'];

    protected $hidden = ['doctor_id', 'updated_at', 'created_at', 'reservation_period', 'order'];

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

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id');
    }

    public function getTimeDurationAttribute()
    {
        return $this->reservation_period;
    }
}
