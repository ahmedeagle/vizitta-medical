<?php

namespace App\Models;

use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use Illuminate\Database\Eloquent\Model;

class DoctorCalculation extends Model
{
    use DoctorTrait, GlobalTrait;
    protected $table = 'doctors';

    protected $hidden = ['pivot', 'specification_id', 'nationality_id', 'provider_id', 'status', 'nickname_id', 'created_at', 'updated_at'];

    protected $appends = ['available_time'];


    public function nickname()
    {
        return $this->belongsTo('App\Models\Nickname', 'nickname_id')->withDefault(["name" => ""]);
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }


    public function mainProvider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id','provider_id')->withDefault(["name" => ""]);
    }

    public function specification()
    {
        return $this->belongsTo('App\Models\Specification', 'specification_id')->withDefault(["name" => ""]);
    }

    public function nationality()
    {
        return $this->belongsTo('App\Models\Nationality', 'nationality_id')->withDefault(["name" => ""]);
    }

    public function manyInsuranceCompanies()
    {
        return $this->hasMany('App\Models\InsuranceCompanyDoctor', 'doctor_id', 'id');
    }

    public function insuranceCompanies()
    {
        return $this->belongsToMany('App\Models\InsuranceCompany', 'insurance_company_doctor', 'doctor_id', 'insurance_company_id');
    }

    public function times()
    {
        return $this->hasMany('App\Models\DoctorTime', 'doctor_id', 'id')->orderBy('order');;
    }

    public function favourites()
    {
        return $this->hasMany('App\Models\Favourite', 'doctor_id', 'id');
    }

    public function promoCodes()
    {
        return $this->hasMany('App\Models\PromoCode', 'doctor_id', 'id');
    }

    public function exceptions()
    {
        return $this->hasMany('App\Models\DoctorExceptions', 'doctor_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'doctor_id');
    }

    public function reservedTimes()
    {
        return $this->hasMany('App\Models\ReservedTime', 'doctor_id');
    }

    public function getAvailableTimeAttribute(){
        $days = $this->times;
        $match = $this->getMatchedDateToDays($days);
        if(!$match || $match['date'] == null)
            return null;
        $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);

        $availableTime = $this->getFirstAvailableTime($this->id, $doctorTimesCount, $days, $match['date'], $match['index']);

       if(count((array) $availableTime))
        return $availableTime['date'] . ' ' . $availableTime['from_time'];

    else
        return null;
    }
}

