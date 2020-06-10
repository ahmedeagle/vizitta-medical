<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    //
    protected $table = 'users';
    protected $forcedNullStrings = ['email', "id_number", 'address', 'birth_date'
        , 'invitation_code', 'insurance_expire_date', 'activation_code', 'insurance_image', 'created_at', 'android_device_hasCode'];
    protected $forcedNullNumbers = ['longitude', 'latitude'];
    protected $casts = [
        'status' => 'integer',
    ];

    protected $fillable = [
        'name', 'mobile', 'id_number', 'email', 'address', 'device_token', 'birth_date', 'status', 'longitude', 'latitude',
        'insurance_image', 'no_of_sms', 'activation_code', 'city_id', 'insurance_company_id', 'api_token', 'password', 'insurance_expire_date', 'token_created_at', 'odoo_user_id'
        , 'android_device_hasCode', 'operating_system', 'invitation_code', 'invitation_points', 'invited_by_code', 'photo', 'gender', 'test','device'
    ];

    protected $hidden = [
        'updated_at', 'no_of_sms', 'city_id', 'insurance_company_id', 'password', 'odoo_user_id', 'operating_system',
        'android',
        'invitation_code',
        'invited_by_code',
    ];

    public static function laratablesCustomAction($user)
    {
        return view('user.actions', compact('user'))->render();
    }

    public function laratablesCreatedAt()
    {
        return Carbon::parse($this->created_at)->format('Y-m-d');
    }

    public function laratablesStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function getUserStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function getInsuranceImageAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }


    public function getPhotoAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function city()
    {
        return $this->belongsTo('App\Models\City', 'city_id')->withDefault(["id" => null, "name" => ""]);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo('App\Models\InsuranceCompany', 'insurance_company_id')->withDefault(["id" => null, "name" => "", "image" => ""]);
    }

    public function tickets()
    {
        return $this->morphMany('App\Models\Ticket', 'actor_id', 'ticketable');
    }

    public function chats()
    {
        return $this->morphMany('\App\Models\Chat', 'chatable');
    }

    public function favourites()
    {
        return $this->hasMany('App\Models\Favourite', 'user_id');
    }

    public function reservations()
    {
        return $this->hasMany('App\Models\Reservation', 'user_id');
    }

    public function records()
    {
        return $this->hasMany('App\Models\UserRecord', 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany('App\Models\UserAttachment', 'user_id');
    }

    public function people()
    {
        return $this->hasMany('App\Models\People', 'user_id');
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

    public function commentReport()
    {
        return $this->hasMany('App\Models\CommentReport', 'user_id');
    }

    public function setPasswordAttribute($password)
    {
        if (!empty($password)) {
            $this->attributes['password'] = bcrypt($password);
        }
    }


    public function getInsuranceExpireDateAttribute($date)
    {
        return ($date != null ? $date : "");
    }

    public function getInvitationCodeAttribute($val)
    {
        return ($val != null ? $val : "");
    }


    public function getInvitedByCodeAttribute($val)
    {
        return ($val != null ? $val : "");
    }

    public function routeNotificationForFcm()
    {
        //return a device token, either from the model or from some other place.
        return $this->device_token;
    }

    public function payments()
    {
        return $this->hasMany('\App\Models\Payment', 'user_id');
    }


    public function point()
    {
        return $this->hasOne('\App\Models\Point', 'user_id');
    }

    public function tokens()
    {
        return $this->hasMany('App\Models\UserToken');
    }

    public function gifts()
    {
        return $this->belongsToMany('App\Models\Gift', 'users_gifts', 'user_id', 'gift_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }


    public function offers()
    {
        return $this->belongsToMany('App\Models\PromoCode', 'user_promocode', 'user_id', 'promocode_id');
    }


    public function getGender()
    {
        if ($this->gender == 1) {
            return "ذكر";
        } elseif ($this->gender == 2) {
            return "أنثي";
        } else
            return "غير محدد";
    }

    public function generalNotifications()
    {
        return $this->morphMany('\App\Models\GeneralNotification', 'notificationable');
    }
}
