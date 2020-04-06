<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservedTime extends Model
{
    protected $table = 'reserved_times';
    public $timestamps = false;

    protected $fillable = ['doctor_id', 'day_date', 'service_id'];

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id')->select('id', 'name_' . app()->getLocale() . ' as name');
    }

    public function service()
    {
        return $this->belongsTo('App\Models\Service', 'service_id')->select('id', 'title_' . app()->getLocale() . ' as title');
    }

    public function getDoctorIdAttribute($value)
    {
        return $value == null ? 0 : $value;
    }

    public function getServiceIdAttribute($value)
    {
        return $value == null ? 0 : $value;
    }

}
