<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    public $timestamps = false;

    protected $fillable = ['name_en', 'name_ar', 'status', 'flag'];

}
