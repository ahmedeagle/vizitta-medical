<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use function foo\func;

class Provider extends Authenticatable implements JWTSubject
{
    use Notifiable;
    protected $table = 'providers';
    public $timestamps = true;
    //because value may send in 1 or '1'  i ensure it is integr only accept 1 not '1'
    protected $casts = [
        'status' => 'integer',
        'lottery' => 'integer',
        'activation' => 'integer',
        'balance' => 'integer',
        /// 'rate'       => 'string'
        'latitude' => 'string',
        'longitude' => 'string',

    ];
    protected $forcedNullStrings = ['email', 'address','address_ar','address_en', 'logo', 'street', 'created_at', 'api_token', 'branch_no', 'rate', 'commercial_en', 'commercial_ar', 'android_device_hasCode'];
    protected $forcedNullNumbers = ['
    ', 'latitude', 'longitude', 'commercial_no', 'application_percentage','application_percentage_for_offers','application_percentage_bill', 'balance', 'application_percentage_bill_insurance'];

    protected $fillable = ['name_en', 'name_ar', 'username', 'password', 'longitude', 'device_token', 'web_token', 'latitude', 'email', 'address',
        'logo', 'mobile', 'commercial_no', 'type_id', 'city_id', 'district_id', 'street', 'provider_id',
        'no_of_sms', 'status', 'activation', 'activation_code', 'activation_code', 'api_token', 'branch_no', 'paid_balance',
        'unpaid_balance', 'application_percentage', 'application_percentage_bill','application_percentage_for_offers','balance', 'commercial_en', 'commercial_ar', 'application_percentage_bill_insurance',
        'odoo_provider_id',
        'android_device_hasCode', 'lottery','address_ar','address_en', 'rate', 'has_home_visit','test'];

    protected $appends = ['is_branch', 'hide',
        'parent_type', 'adminprices',
        'provider_has_bill',
        'has_insurance',
        'is_lottery',
        'rate_count',
    ];  // to append coulms to table virtual

    protected $hidden = [
        'created_at', 'password' /*, 'city_id', 'type_id', 'district_id'*/, 'updated_at', 'no_of_sms', 'activation', /*'device_token',*/ 'web_token', 'application_percentage', 'application_percentage_bill',
        'odoo_provider_id', 'adminprices'
    ];


    public static function laratablesCustomAction($provider)
    {
        return view('provider.actions', compact('provider'))->render();
    }

    public function getHideAttribute()
    {
        return !$this->status;
    }


    public function getProviderHasBillAttribute()
    {

        $this->makeVisible(['application_percentage_bill']);
        $bill_tax = Provider::where('id', $this->id)->value('application_percentage_bill');
        if (!is_numeric($bill_tax) || $bill_tax <= 0) {
            return 0;
        }
        return 1;
    }

    public function laratablesLottery()
    {
        return $this->lottery == 0 ? 'لا' : 'نعم';

    }

    public function getProviderLottery()
    {
        return $this->lottery == 0 ? 'لا' : 'نعم';

    }

    public static function laratablesCustomAdminAction($provider)
    {
        return view('admin.actions', compact('provider'))->render();
    }

    public static function laratablesCustomAdminBranchesAction($provider)
    {
        return view('admin.branchesActions', compact('provider'))->render();
    }

    public static function laratablesCustomBranchAction($branch)
    {
        return view('branch.actions', compact('branch'))->render();
    }

    public static function laratablesCustomLotteryAction($branch)
    {
        return view('lotteries.actions', compact('branch'))->render();
    }


    public function laratablesStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function getProviderStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function laratablesCreatedAt()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d');
    }

    public function getLogoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function getAddressArAttribute($val)
    {
        return ($val != null ?  $val : "");
    }
    public function getAddressEnAttribute($val)
    {
        return ($val != null ?  $val  : "");
    }

    public function getTranslatedName()
    {
        return $this->{'name_' . app()->getLocale()};
    }

    public function countDoctorTimesInDay($query, $dayCode)
    {
        return $query->doctors()->whereHas('times', function ($q) use ($dayCode) {
            $q->whereRaw('LOWER(day_code) = ?', strtolower($dayCode));
        })->select(DB::raw('COUNT(*)'));
    }

    public function type()
    {
        return $this->belongsTo('App\Models\ProviderType', 'type_id', 'id')->withDefault(["name" => ""]);
    }

    public function getParentTypeAttribute()
    {
        if ($this->provider_id != null) {
            $provider = Provider::find($this->provider_id);
            if ($provider)
                return ProviderType::select('id', DB::raw('name_' . app()->getLocale() . ' as name'))->find($provider->type_id);
            else
                return ['id' => 0, 'name' => ''];
        } else
            return ['id' => 0, 'name' => ''];
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City', 'city_id');
    }

    public function district()
    {
        return $this->belongsTo('App\Models\District', 'district_id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function main_provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function providers()
    {
        return $this->hasMany('App\Models\Provider', 'provider_id', 'id');
    }

    public function doctors()
    {

        return $this->hasMany('App\Models\Doctor', 'provider_id', 'id');
    }

    public function tickets()
    {
        return $this->morphMany('App\Models\Ticket', 'ticketable');
    }

    public function favourites()
    {
        return $this->hasMany('App\Models\Favourite', 'provider_id', 'id');
    }

    public function promoCodes()
    {
        return $this->hasMany('App\Models\PromoCode', 'provider_id', 'id');
    }

    //get main provider payments of promocode
    public function payments()
    {
        return $this->hasManyThrough(
            'App\Models\Payment',
            'App\Models\PromoCode',
            'provider_id',
            'offer_id',
            'id',
            'id'
        );
    }

    public function customPages()
    {
        return $this->hasMany('App\Models\CustomPage', 'provider_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'provider_id', 'id');
    }

    public function doctorTimes()
    {
        return $this->hasMany('App\Models\DoctorTime', 'provider_id', 'id');
    }


    public function getAdminpricesAttribute()
    {

        if ($this->provider_id != null)
            return 0;

        $this->makeVisible('provider_id');

        return $this->providers()->sum('balance');

    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";
        else if (in_array($key, $this->forcedNullNumbers) && $value === null)
            $value = 0;

        return parent::setAttribute($key, $value);
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

    public function getIsBranchAttribute()
    {
        if ($this->provider_id == null)
            return 0;
        return 1;
    }


    //is branch has doctors with insurance
    public function getHasInsuranceAttribute()
    {
        if ($this->provider_id != null) {  // branch
            $branchDoctorId = $this->doctors()->pluck('id');
            $doctorsHasInsurance = InsuranceCompanyDoctor::whereIn('doctor_id', $branchDoctorId)->count();
            if ($doctorsHasInsurance > 0)
                return 1;
            else
                return 0;
        } else { //provide
            /*  $branchesId = Provider::where('provider_id', $this->id)->pluck('id');
              $doctorIds = Doctor::whereIn('provider_id', $branchesId)->pluck('id');
              $doctorsHasInsurance = InsuranceCompanyDoctor::whereIn('doctor_id', $doctorIds)->count();
              if ($doctorsHasInsurance > 0)
                  return 1;
              else
                  return 0;*/
            return 0;
        }
    }

    public function getRateCountAttribute()
    {
        if ($this->provider_id != null) {  // branch
            return $this->reservations()->Where('provider_rate', '!=', null)
                ->Where('provider_rate', '!=', 0)->count();
        } else { //provider
            $branchesId = $this->providers()->pluck('id');
            if (count($branchesId) > 0)
                return Reservation::whereIn('provider_id', $branchesId)
                    ->Where('provider_rate', '!=', null)
                    ->Where('provider_rate', '!=', 0)
                    ->count();
            else
                return 0;
        }
    }

    public function getIsLotteryAttribute()
    {
        if ($this->lottery == 0)
            return 0;
        else
            return 1;
    }


    public function routeNotificationForFcm()
    {
        //return a device token, either from the model or from some other place.
        return $this->device_token;
    }

    public function scopeBranch($query)
    {

        return $query->whereNotNull('provider_id');
    }

    public function scopeProviderSelection($query)
    {

        return $query->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), 'address', 'latitude', 'longitude', 'logo','mobile', 'rate');//
    }

    public function tokens()
    {
        return $this->hasMany('App\Models\Token');
    }


    public function getBalanceAttribute($val)
    {
        return ($val != null ? $val : 0);
    }

    //for featured branched
    public function subscriptions()
    {
        return $this->hasOne('App\Models\FeaturedBranch', 'branch_id', 'id');
    }

    public function gifts()
    {
        return $this->hasMany('App\Models\Gift', 'branch_id', 'id');
    }

    public function scopeWithdrawable($query)
    {
        return $query->where('lottery', 1)->where('provider_id', null)->whereHas('gifts', function ($query) {
            $query->where('amount', '>', 0);
        });
    }


    public function generalNotifications()
    {
        return $this->morphMany('\App\Models\GeneralNotification', 'notificationable');
    }

    public function branches()
    {
        return $this->hasMany('App\Models\Provider', 'provider_id', 'id');
    }

    public function services()
    {
        return $this->hasMany('App\Models\Service', 'branch_id', 'id');
    }

    public function homeServices()
    {
        return $this->hasMany('App\Models\Service', 'branch_id', 'id')->whereHas('types', function ($q) {
            $q->where('services_type.id',1);
        });    }

    public function clinicServices()
    {
        return $this->hasMany('App\Models\Service', 'branch_id', 'id')->whereHas('types', function ($q) {
            $q->where('services_type.id',2);
        });
    }


    public function test(){
        return $this -> hasMany('App\Models\Test','provider_id','id');
    }
}


