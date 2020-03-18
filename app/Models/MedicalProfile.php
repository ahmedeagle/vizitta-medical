<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalProfile extends Model
{
    protected $table = 'medical_profile';

    protected $fillable = ['blood', 'user_id'];
    protected $hidden = ['user_id', 'created_at', 'updated_at'];

   /* public function sensitivities()
    {
        return $this->hasManyThrough('App\Models\Sensitivity', 'App\Models\MedicalProfileSensitivity', 'medical_profile_id', 'id', 'id', 'sensitivity_id');
    }*/

    public function sensitivities /*MedicalProfileSensitivity*/()
    {
        return $this->hasMany('App\Models\MedicalProfileSensitivity', 'medical_profile_id');
    }
    public function disease()
    {
        return $this->hasMany('App\Models\MedicalProfileDiseases', 'medical_profile_id');
    }
    public function pharmaceuticals()
    {
        return $this->hasMany('App\Models\MedicalProfilePharmaceutical', 'medical_profile_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}

