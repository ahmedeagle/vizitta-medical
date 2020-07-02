<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HyperPayTransaction extends Model
{
    protected $table = 'hyperpay_transactions';
    public $timestamps = false;

    protected $fillable = ['random_id', 'checkout_id','transaction_id'];

}
