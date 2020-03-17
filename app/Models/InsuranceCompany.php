<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;

class InsuranceCompany extends Model
{
    protected $table = 'insurance_companies';
    public $timestamps = true;
    protected $forcedNullStrings = ['image'];
    protected $forcedNullNumbers = [];

    protected $fillable = ['name_en', 'name_ar', 'status', 'image'];
    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    public static function laratablesCustomAction($company)
    {
        return view('insurance-company.actions', compact('company'))->render();
    }

    public function laratablesStatus( )
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public function getImageAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }

    public function doctors()
    {
        return $this->belongsToMany('App\Models\Doctor', 'insurance_company_doctor', 'insurance_company_id', 'doctor_id');
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";
        else if (in_array($key, $this->forcedNullNumbers) && $value === null)
            $value = 0;

        return parent::setAttribute($key, $value);
    }


}
