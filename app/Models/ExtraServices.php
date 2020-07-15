<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraServices extends Model
{
    protected $table = 'extra_services';
    public $timestamps = true;
    protected $fillable = ['name', 'reservation_id', 'price', 'created_at', 'updated_at'];
    protected $hidden =['created_at','updated_at','reservation_id'];

    public function reservation()
    {
        return $this->belongsTo('App\Models\ServiceReservation', 'reservation_id');
    }


}
