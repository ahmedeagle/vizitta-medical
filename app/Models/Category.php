<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar'];

    protected $hidden = ['created_at', 'updated_at'];

    public static function laratablesCustomAction($city)
    {
        return view('category.actions', compact('category'))->render();
    }

    public function user_attachments()
    {
        return $this->belongsTo('App\Models\UserAttachment', 'category_id');
    }
}
