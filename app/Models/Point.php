<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $table = 'points';
    public $timestamps = true;

    protected $fillable = ['user_id', 'reservation_id','points'];

    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {

        return $this->belongsTo('\App\Models\User', 'user_id');
    }
}
