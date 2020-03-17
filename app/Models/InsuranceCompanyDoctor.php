<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCompanyDoctor extends Model
{
    protected $table = 'insurance_company_doctor';
    public $timestamps = true;

    protected $fillable = ['doctor_id', 'insurance_company_id'];

    protected $hidden = ['pivot'];

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id');
    }

    public function insuranceCompany()
    {
        return $this->belongsTo('App\Models\InsuranceCompany', 'insurance_company_id');
    }

}
