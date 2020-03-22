<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRecord extends Model
{
    protected $table = 'user_records';
    public $timestamps = true;
    protected $fillable = ['user_id', 'specification_id','reservation_no', 'day_date', 'summary','provider_id','doctor_id'];
    protected $hidden = ['user_id', 'specification_id', 'updated_at', 'reservation_no','provider_id','doctor_id'];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function specification()
    {
        return $this->belongsTo('App\Models\Specification', 'specification_id');
    }


     public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id');
    }


    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }



    public function attachments()
    {
        return $this->hasMany('App\Models\UserAttachment', 'record_id');
    }

    public function reservation()
    {
        return $this->belongsTo('App\Models\Reservation', 'reservation_no', 'reservation_no')->withDefault(['id'=>0]);
    }
}
