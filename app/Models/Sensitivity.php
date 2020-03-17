<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensitivity extends Model
{
    protected $table = 'sensitivity';

    protected $fillable = ['name_en', 'name_ar'];

    protected $hidden = ['created_at', 'updated_at','laravel_through_key'];

    public function medicalProfile()
    {
        return $this->hasMany('App\Models\MedicalProfileSensitivity', 'sensitivity_id');
    }

}
