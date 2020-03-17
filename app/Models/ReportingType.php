<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingType extends Model
{
    protected $table = 'reporting_types';

    protected $fillable = ['name_en', 'name_ar'];

    protected $hidden = ['created_at', 'updated_at','laravel_through_key'];

    public function CommentReport()
    {
        return $this->hasMany('App\Models\CommentReport', 'sensitivity_id');
    }

}
