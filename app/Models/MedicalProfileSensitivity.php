<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalProfileSensitivity extends Model
{
    protected $table = 'medical_profile_sensitivity';
    public $timestamps = true;

    protected $fillable = ['medical_profile_id', 'sensitivity_id'];

    protected $hidden = ['pivot'];

    public function medicalProfile()
    {
        return $this->belongsToMany('App\Models\MedicalProfile', 'medical_profile', 'id', 'medical_profile_id');
    }

    public function sensitivity()
    {
        return $this->belongsToManyth('App\Models\Sensitivity', 'sensitivity', 'id', 'sensitivity_id');
    }


}
