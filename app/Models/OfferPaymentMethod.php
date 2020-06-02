<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OfferPaymentMethod extends Pivot
{
    protected $table = 'offer_payment_methods';
    public $timestamps = false;

    protected $fillable = ['payment_method_id', 'offer_id', 'payment_amount_type', 'payment_amount'];

    public function getPaymentAmountTypeAttribute($val)
    {
        return (  $val != null ? $val : '');
    }

    public function getPaymentAmountAttribute($val)
    {
        return ($val != null ? $val : '0');
    }
}
