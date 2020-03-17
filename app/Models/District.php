<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar', 'city_id'];

    protected $hidden = ['created_at', 'updated_at'];

    public function city()
    {
        return $this->belongsTo('App\Models\City', 'city_id');
    }

    public function providers()
    {
        return $this->hasMany('App\Models\Provider', 'district_id');
    }

    public static function laratablesCustomAction($district)
    {
        return view('district.actions', compact('district'))->render();
    }

}
