<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderType extends Model
{
    protected $table = 'provider_types';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar', 'status'];

    public function getName()
    {
        return $this->{'name_' . app()->getLocale()};
    }

    public function providers()
    {
        return $this->hasMany('App\Models\Provider', 'type_id');
    }


    public static function laratablesCustomAction($type)
    {
        return view('types.actions', compact('type'))->render();
    }

    public function scopeActive($query)
    {
        return $query -> where('status',1);
    }
}
