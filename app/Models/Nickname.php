<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nickname extends Model
{
    protected $table = 'doctor_nicknames';
    public $timestamps = false;

    protected $fillable = ['name_en', 'name_ar', 'status'];

    public static function laratablesCustomAction($nickname)
    {
        return view('nickname.actions', compact('nickname'))->render();
    }

    public function doctors()
    {
        return $this->hasMany('App\Models\Doctor', 'nickname_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

}
