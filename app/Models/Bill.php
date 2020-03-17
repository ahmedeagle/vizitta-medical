<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $table = 'bills';
    public $timestamps = true;

    protected $fillable = ['photo', 'reservation_id', 'reservation_no'];

    protected $hidden = ['created_at', 'updated_at'];



    public static function laratablesCustomAction($bill)
    {
        return view('bills.actions', compact('bill'))->render();
    }

    public function getPhotoAttribute($val)
    {
        $this->reservation_id;
        return ($val != "" ? asset($val) : "");
    }

    public function reservation()
    {

        return $this->belongsTo('\App\Models\Reservation', 'reservation_id');
    }
}
