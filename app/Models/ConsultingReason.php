<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultingReason extends Model
{
    protected $table = 'consulting_reasons';
    public $timestamps = true;

    protected $fillable = ['name_en', 'name_ar'];

    protected $hidden = ['created_at', 'updated_at'];



}
