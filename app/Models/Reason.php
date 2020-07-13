<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    protected $table = 'reasons';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar','status'];

    protected $hidden = ['created_at', 'updated_at'];

    public static function laratablesCustomAction($reason)
    {
        return view('reasons.actions', compact('reason'))->render();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

}
