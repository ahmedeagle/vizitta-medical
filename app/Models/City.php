<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $forcedNullStrings = ['name_en', 'name_ar'];

    public static function laratablesCustomAction($city)
    {
        return view('city.actions', compact('city'))->render();
    }

    public function districts()
    {
        return $this->hasMany('App\Models\District', 'city_id', 'id');
    }

    public function providers()
    {
        return $this->hasMany('App\Models\Provider', 'city_id');
    }
}
