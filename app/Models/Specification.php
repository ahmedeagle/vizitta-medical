<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specification extends Model
{
    protected $table = 'specifications';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar','status'];

    protected $hidden = ['created_at', 'updated_at'];

    public static function laratablesCustomAction($specification)
    {
        return view('specification.actions', compact('specification'))->render();
    }

    //removed promocodes not related with  specification now
    public function promoCodes()
    {
        return $this->hasMany('App\Models\PromoCode', 'specification_id', 'id');
    }

    public function doctors()
    {
        return $this->hasMany('App\Models\Doctor', 'specification_id', 'id');
    }

    public function services()
    {
        return $this->hasMany('App\Models\Service', 'specification_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query -> where('status',1);
    }

}
