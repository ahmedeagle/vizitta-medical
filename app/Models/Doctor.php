<?php

namespace App\Models;

use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Doctor extends Authenticatable implements JWTSubject
{
    use DoctorTrait, GlobalTrait, Notifiable;
    protected $table = 'doctors';
    public $timestamps = true;
    protected $forcedNullStrings = ['photo', 'information_en', 'information_ar'];
    protected $forcedNullNumbers = ['reservation_period', 'application_percentage'];
    protected $casts = [
        'status' => 'integer',
    ];

    protected $fillable = ['doctor_type', 'is_consult', 'name_en', 'name_ar', 'username', 'phone', 'password', 'gender', 'photo', 'information_en', 'information_ar', 'nickname_id',
        'provider_id', 'specification_id', 'nationality_id', 'price', 'price_consulting', 'status', 'rate', 'reservation_period', 'abbreviation_ar', 'abbreviation_en', 'waiting_period', 'application_percentage', 'balance', 'phone'];

    protected $hidden = ['pivot', 'password', 'specification_id', 'nationality_id', 'provider_id', 'status', 'nickname_id', 'created_at', 'updated_at'];
    protected $appends = ['available_time', 'hide'];

    public static function laratablesCustomAction($doctor)
    {
        return view('doctor.actions', compact('doctor'))->render();
    }

    public static function laratablesCustomProviderfullname($doctor)
    {
        $doctor = Doctor::find($doctor->id);
        if (!$doctor)
            return '';

        $branchName = Provider::find($doctor->provider_id);

        if (!$branchName)
            return '';
        $mainProvider = Provider::find($branchName->provider_id);
        if (!$mainProvider)
            return $branchName->name_ar;
        $fullName = $branchName->name_ar . ' - ' . $mainProvider->name_ar;
        return $fullName;
    }

    public function setReservationPeriodAttribute($value)
    {

        if ($value == 0 or $value == null or $value == '') {
            $this->attributes['reservation_period'] = 15;  // default 15 minutes
        } else
            $this->attributes['reservation_period'] = $value;
    }

    public function laratablesStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function getHideAttribute()
    {
        return !$this->status;
    }

    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function getTranslatedName()
    {
        return $this->{'name_' . app()->getLocale()};
    }

    public function getTranslatedInformation()
    {
        return $this->{'information_' . app()->getLocale()};
    }

    public function getTranslatedAbbreviation()
    {
        return $this->{'abbreviation_' . app()->getLocale()};
    }

    public function nickname()
    {
        return $this->belongsTo('App\Models\Nickname', 'nickname_id')->withDefault(["name" => ""]);
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }


    public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function chats()
    {
        return $this->morphMany('\App\Models\Chat', 'chatable');
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
        return $this->hasMany('App\Models\DoctorTime', 'doctor_id', 'id')->orderBy('order');
    }

    public function TimesCode()
    {
        return $this->hasMany('App\Models\DoctorTime', 'doctor_id', 'id');
    }

    ########### Start Consultative Doctor Times ##########

    public function consultativeTimes()
    {
        return $this->hasMany('App\Models\ConsultativeDoctorTime', 'doctor_id', 'id')->orderBy('order');
    }

    public function consultativeTimesCode()
    {
        return $this->hasMany('App\Models\ConsultativeDoctorTime', 'doctor_id', 'id');
    }

    ########### End Consultative Doctor Times ##########

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

    public function doctorConsultingReservations()
    {
        return $this->hasMany('App\Models\DoctorConsultingReservation', 'doctor_id');
    }

    public function reservedTimes()
    {
        return $this->hasMany('App\Models\ReservedTime', 'doctor_id');
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";
        else if (in_array($key, $this->forcedNullNumbers) && $value === null)
            $value = 0;
        return parent::setAttribute($key, $value);
    }

    public function getAvailableTimeAttribute()
    {
        try {
            $days = $this->times;
            $match = $this->getMatchedDateToDays($days);
            if (!$match || $match['date'] == null)
                return null;
            $doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);

            $availableTime = $this->getFirstAvailableTime($this->id, $doctorTimesCount, $days, $match['date'], $match['index']);

            if (count((array)$availableTime))
                return $availableTime['date'] . ' ' . $availableTime['from_time'];

            else
                return null;
        } catch (\Exception $ex) {
            return null;
        }
    }

    public function getAbbreviationArAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getAbbreviationEnAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getWaitingPeriodAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getPhoneAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getPriceAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getPriceConsultingAttribute($val)
    {
        return ($val !== null ? $val : "");
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setPasswordAttribute($password)
    {
        if (!empty($password)) {
            $this->attributes['password'] = bcrypt($password);
        }
    }

}


