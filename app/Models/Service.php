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
//
    protected $fillable = ['title_ar', 'price', 'title_en', 'information_ar', 'information_en', 'provider_id', 'branch_id', 'specification_id', 'clinic_price', 'home_price', 'clinic_price_duration', 'home_price_duration', 'status', 'type', 'rate', 'reservation_period', 'created_at', 'updated_at'];
    protected $hidden = ['specification_id', 'status', 'created_at', 'updated_at'];

    protected $forcedNullStrings = ['reservation_period', 'home_price_duration', 'clinic_price_duration'];

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


    //sat sun mon
    public function TimesCode()
    {
        return $this->hasMany('App\Models\ServiceTime', 'service_id', 'id');
    }

    public function serviceType()
    {
        return $this->belongsTo('App\Models\ServiceType', 'type', 'id')->withDefault(["name" => ""]);
    }

    public function types()
    {
        return $this->belongsToMany('App\Models\ServiceType', 'services_type_pivote', 'service_id', 'type_id', 'id', 'id');
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
            return [];
        } catch (\Exception $ex) {
            return [];
        }
    }


    public function reservations()
    {
        return $this->hasMany('App\Models\ServiceReservation', 'service_id');
    }

    /*   public function reservedTimes()
       {
           return $this->hasMany('App\Models\ReservedTime', 'service_id');
       }*/

    public function getPriceTypeAttribute($value)
    {
        return $value != null ? $value : '';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getClinicPriceDurationAttribute($value)
    {
        return $value != null ? $value : '';
    }

   /* public function getHomePriceDurationAttribute($value)
    {
        return $value != null ? $value : '';
    }


    public function getClinicPriceAttribute($value)
    {
        return $value != null ? $value : '';
    }*/

    public function getHomePriceAttribute($value)
    {
        return $value != null ? $value : '';
    }


    //we change price to to other price in all project and not need to edit the key in all places

    public function getPriceAttribute($val)
    {
        if ($this->clinic_price != null && $this->home_price != null) {
            return $this->clinic_price <= $this->home_price ? (string)$this->clinic_price : (string)$this->home_price;
        } elseif ($this->clinic_price != null) {
            return (string)$this->clinic_price;
        } elseif ($this->clinic_price != null) {
            return (string)$this->clinic_price;
        } else {
            return '0';
        }
    }

}

