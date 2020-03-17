<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands';
    protected $forcedNullStrings = ['photo'];
    public $timestamps = false;

    protected $fillable = [
        'photo'
    ];

    protected $hidden = [
        'updated_at'
    ];

    public static function laratablesCustomAction($brand)
    {
        return view('brands.actions', compact('brand'))->render();
    }

    public function getPhotoAttribute($val)
    {
        if ($val != "") {
            $res = '<img style="width:100px;" src="' . asset($val) . '">';
        } else {
            $res = '';
        }
        return $res;
    }


}
