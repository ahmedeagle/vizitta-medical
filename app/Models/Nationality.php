<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    protected $table = 'nationalities';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar'];

    public function doctors()
    {
        return $this->hasMany('App\Models\Doctor', 'nationality_id');
    }

    public static function laratablesCustomAction($nationality)
    {
        return view('nationality.actions', compact('nationality'))->render();
    }
}
