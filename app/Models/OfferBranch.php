<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferBranch extends Model
{
    protected $table = 'offers_branches';
    public $timestamps = false;

    protected $fillable = ['offer_id', 'branch_id'];
    protected $hidden = ['created_at', 'updated_at', 'status'];


    public function offer()
    {
        return $this->belongsTo('App\Models\Offer', 'offer_id');
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'branch_id', 'id');
    }

    public function laratablesStatus()
    {
        return ($this->status ? 'مفعّل' : 'غير مفعّل');
    }

    public static function laratablesCustomAction($offer)
    {
        return view('offer.branches_actions', compact('offer'))->render();
    }
}
