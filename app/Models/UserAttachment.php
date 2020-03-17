<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAttachment extends Model
{
    protected $table = 'user_attachments';
    public $timestamps = true;
    protected $fillable = ['user_id', 'record_id', 'attachment', 'category_id'];
    protected $hidden = ['category_id','user_id', 'record_id', 'updated_at', 'created_at'];

    public function getAttachmentAttribute($val)
    {
        return ($val != "" ? asset($val) : "");
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function record()
    {
        return $this->belongsTo('App\Models\UserRecord', 'record_id');
    }

    public function category()
    {
        return $this->hasOne('App\Models\Category', 'id', 'category_id');
    }

    public function getAttachment()
    {
        return public_path() . 'users/' . $this->attachment;
    }
}
