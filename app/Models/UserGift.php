<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGift extends Model
{
    protected $table = 'users_gifts';
    public $timestamps = true;

    protected $fillable = ['user_id', 'gift_id'];

    protected $hidden = ['pivot'];

}
