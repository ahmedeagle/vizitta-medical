<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    protected $table = 'people';
    public $timestamps = true;
    protected $fillable = ['name', 'birth_date', 'user_id', 'insurance_company_id', 'insurance_image', 'phone'];
    protected $hidden = ['user_id', 'insurance_company_id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id')->withDefault(["name" => ""]);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo('App\Models\InsuranceCompany', 'insurance_company_id')->withDefault(["name" => ""]);
    }

    public function getInsuranceImageAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function getPhoneAttribute($val)
    {
        return $val == null ? '' : $val;
    }

    public function setPhoneAttribute($phone)
    {
        if ($phone == null) {
            $this->attributes['phone'] = '';
        } else
            $this->attributes['phone'] = $phone;
    }

}
