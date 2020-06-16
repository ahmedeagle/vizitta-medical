<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reciever extends Model
{
    protected $table = 'admin_notifications_receivers';
    public $timestamps = true;

    protected $fillable = ['notification_id', 'actor_id', 'actor_type', 'seen', 'device', 'created_at'];
    protected $hidden = ['updated_at', 'device'];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'actor_id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'actor_id');
    }

    public function notification()
    {

        return $this->belongsTo('App\Models\Notification', 'notification_id', 'id');
    }

    public function scopeUnseenForUser($query)
    {
        return $query->where('actor_type', 'users')->where('seen', '0');
    }

    public function scopeUnseenForProvider($query)
    {
        return $query->where('actor_type', 'providers')->where('seen', '0');
    }
    public function scopeForProvider($query)
    {
        return $query->where('actor_type', 'providers');
    }

    public function getCreatedAtAttribute($val)
    {
        return $val !== null ? $val : "";
    }
}
