<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    protected $table = 'comments_report';

    protected $fillable = ['reservation_no', 'reporting_type_id', 'report_comment', 'provider_id', 'user_id'];

    protected $hidden = ['created_at', 'updated_at','laravel_through_key'];


    public static function laratablesCustomAction($report)
    {
        return view('reports.actions', compact('report'))->render();
    }

    public function reportingType()
    {
        return $this->belongsTo('App\Models\ReportingType', 'reporting_type_id');
    }

    public function reservation()
    {
        return $this->belongsTo('App\Models\Reservation', 'reservation_no');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

}
