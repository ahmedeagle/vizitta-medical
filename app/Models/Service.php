<?php

namespace App\Models;

use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use  GlobalTrait;
    protected $table = 'services';
    public $timestamps = true;

    protected $fillable = ['title_ar', 'title_en', 'information_ar', 'information_en', 'provider_id', 'branch_id', 'specification_id', 'price', 'status', 'type', 'rate', 'reservation_period', 'created_at', 'updated_at'];
    protected $hidden = ['specification_id', 'status', 'created_at', 'updated_at'];

    protected $forcedNullStrings = ['reservation_period'];
    protected $casts = [
        'status' => 'integer',
    ];
    protected $appends = ['available_time', 'hide'];


    public function getHideAttribute()
    {
        return !$this->status;
    }


    public function getTranslatedName()
    {
        return $this->{'title_' . app()->getLocale()};
    }

    public function getTranslatedInformation()
    {
        return $this->{'information_' . app()->getLocale()};
    }


    public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'branch_id')->withDefault(["name" => ""]);
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function specification()
    {
        return $this->belongsTo('App\Models\Specification', 'specification_id')->withDefault(["name" => ""]);
    }


    public function times()
    {
        return $this->hasMany('App\Models\ServiceTime', 'service_id', 'id');
    }


    public function TimesCode()
    {
        return $this->hasMany('App\Models\ServiceTime', 'service_id', 'id');
    }

    public function serviceType()
    {
        return $this->belongsTo('App\Models\ServiceType', 'type', 'id')->withDefault(["name" => ""]);
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";
        return parent::setAttribute($key, $value);
    }

    public function getReservationPeriodAttribute($value)
    {
        return $value != null ? $value : 0;
    }


    public function getAvailableTimeAttribute()
    {
        try {
            return null;
        } catch (\Exception $ex) {
            return null;
        }
    }


    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'service_id');
    }

    public function reservedTimes()
    {
        return $this->hasMany('App\Models\ReservedTime', 'service_id');
    }
}
