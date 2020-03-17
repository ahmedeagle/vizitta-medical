<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $table = 'user_favourites';
    public $timestamps = true;

    protected $fillable = ['user_id', 'doctor_id', 'provider_id'];

    protected $hidden = ['updated_at', 'user_id', 'doctor_id', 'provider_id'];

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}
