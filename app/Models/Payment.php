<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = true;
    protected $fillable = ['user_id', 'invited_user_mobile', 'for_me', 'amount', 'offer_id', 'payment_no', 'current_amount', 'code', 'odoo_offer_id','provider_value_of_coupon'];
    protected $hidden = ['updated_at', 'paid','odoo_offer_id'];


    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function offer()
    {

        return $this->belongsTo('App\Models\PromoCode', 'offer_id', 'id');
    }


}
