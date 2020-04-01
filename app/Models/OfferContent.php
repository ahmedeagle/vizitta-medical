<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferContent extends Model
{
    protected $table = 'offer_contents';
    public $timestamps = false;
    protected $fillable = ['offer_id', 'content_ar', 'content_en', 'sort'];
    protected $hidden = ['offer_id'];
}
