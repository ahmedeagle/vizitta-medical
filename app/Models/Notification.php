<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'admin_notifications';
    public $timestamps = true;

    protected $fillable = ['title', 'content', 'type','allow_fire_base','created_at', 'photo','notifictionable_type','notifictionable_id','subCategory_id','external_link'];
    protected $hidden = ['updated_at'];

    public function recievers()
    {
        return $this->hasMany('App\Models\Reciever', 'notification_id');
    }


    public function getPhotoAttribute($val)
    {
        return $val !== null ? asset($val) : "";

    }

    public function notificationable()
    {
        return $this->morphTo();
    }

    public static function laratablesCustomAction($notify)
    {

        return view('notifications.actions', compact('notify'))->render();
    }

    public function getExternalLinkAttribute($val)
    {
        return ($val != null ? $val : "");
    }


}
