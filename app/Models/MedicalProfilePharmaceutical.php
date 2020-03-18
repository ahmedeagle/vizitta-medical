<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalProfilePharmaceutical extends Model
{
    protected $table = 'medical_profile_pharmaceutical';
    public $timestamps = true;

    protected $fillable = ['medical_profile_id', 'pharmaceutical_name'];

    protected $hidden = ['pivot'];

    public function medicalProfile()
    {
        return $this->belongsToMany('App\Models\MedicalProfile', 'medical_profile', 'id', 'medical_profile_id');
    }

}
