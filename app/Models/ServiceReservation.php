<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServiceReservation extends Model
{
    protected $table = 'service_reservations';
    public $timestamps = true;
    protected $forcedNullStrings = ['transaction_id', 'bill_photo', 'reservation_no', 'rejection_reason', 'price', 'provider_rate', 'service_rate', 'rate_comment', 'bill_total', 'last_day_date', 'last_from_time', 'last_to_time', 'user_rejection_reason', 'service_rate', 'address'];
    protected $forcedNullNumbers = [];

    protected $fillable = ['application_balance_value', 'payment_type', 'custom_paid_price', 'remaining_price', 'reservation_no', 'has_extra_services', 'transaction_id', 'user_id', 'service_id', 'day_date', 'from_time', 'to_time', 'payment_method_id', 'paid',
        'approved', 'order', 'provider_id', 'branch_id', 'service_rate', 'provider_rate', 'rate_comment', 'rate_date', 'rejection_reason', 'price', 'total_price', 'is_visit_doctor', 'bill_total', 'discount_type',
        'bill_photo', 'last_day_date', 'last_from_time', 'last_to_time', 'user_rejection_reason',
        'service_id', 'service_rate', 'address', 'service_type', 'latitude', 'longitude', 'hours_duration', 'rejected_reason_id', 'rejected_reason_notes'];

    protected $hidden = ['bill_photo', 'updated_at', 'user_id', 'service_id', 'payment_method_id', 'people_id', 'discount_type'];
    protected $appends = ['for_me', 'branch_name', 'branch_no', 'is_reported', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'reservation_total', 'rejected_reason_type'];

    public function getIsReportedAttribute()
    {
        if (isset($this->commentReport) && isset($this->commentReport->id))
            return '1';
        else
            return '';
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id')->withDefault(["name" => ""]);
    }

    public function service()
    {
        return $this->belongsTo('App\Models\Service', 'service_id')->withDefault(["name" => ""]);
    }

    public function serviceTypes()
    {
        return $this->belongsToMany('App\Models\ServiceType', 'services_type_pivote', 'service_id', 'type_id', 'id', 'id');
    }

    public function mainProvider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id', 'provider_id')->withDefault(["name" => ""]);
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id', 'provider_id')->withDefault(["name" => ""]);
//        return $this->belongsTo('App\Models\Provider', 'branch_id')->whereNotNull('provider_id')->withDefault(["name" => ""]);
    }

    public function branchId()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id', 'address', 'street', 'latitude', 'longitude');
    }

    public function records()
    {
        return $this->hasMany('App\Models\UserRecord', 'reservation_no')->withDefault(["name" => ""]);
    }

    public function extraServices()
    {
        return $this->hasMany('App\Models\ExtraServices', 'reservation_id');
    }


    /*public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }*/

    public function paymentMethod()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_method_id')->withDefault(["name" => ""]);
    }

    public function getApproved()
    {
        if ($this->approved == '0')
            $result = __('main.pending');
        elseif ($this->approved == '1')
            $result = __('main.confirmed');
        elseif ($this->approved == '2')
            $result = __('main.canceled');
        elseif ($this->approved == '3')
            $result = __('main.done');
        else
            $result = __('main.not_found');
        return $result;
    }

    /* public function getApproved()
    {
        if ($this->approved == 1)
            $result = 'موافق عليه';
        elseif ($this->approved == 2 && $this->rejection_reason != null && $this->rejection_reason != "" && $this->rejection_reason != 0)
            $result = 'مرفوض بواسطه العياده';
        elseif ($this->approved == 3)
            $result = ' مكتمل بزياره العميل';
        elseif ($this->approved == 2 && ($this->rejection_reason == null or $this->rejection_reason == "" or $this->rejection_reason == 0))
            $result = ' مكتمل بعدم زياره العميل';
        elseif ($this->approved == 4)
            $result = 'مشغول';
        elseif ($this->approved == 5)
            $result = 'مرفوض بواسطه المستخدم ';
        else
            $result = 'معلق';
        return $result;
    }*/

    /*public function getStatus()
    {
        if ($this->status == 'pending')
            $result = __('main.pending');
        elseif ($this->status == 'confirmed')
            $result = __('main.confirmed');
        elseif ($this->status == 'done')
            $result = __('main.done');
        elseif ($this->status == 'canceled')
            $result = __('main.canceled');
        else
            $result = __('main.not_found');
        return $result;
    }*/

    public function getRejectedReasonTypeAttribute()
    {
        if ($this->rejected_reason_id == 1)
            $result = app()->getLocale() == 'ar' ? 'كنت أقوم بالتجربة فقط' : 'I was just experimenting';
        elseif ($this->rejected_reason_id == 2)
            $result = app()->getLocale() == 'ar' ? 'حجز مكرر' : 'Duplicate reservation';
        elseif ($this->rejected_reason_id == 3)
            $result = app()->getLocale() == 'ar' ? 'حجزت بالخطأ' : 'Booked by mistake';
        elseif ($this->rejected_reason_id == 4)
            $result = app()->getLocale() == 'ar' ? 'لم أعد أرغب بالموعد' : 'I no longer want the appointment';
        else
            $result = null;
        return $result;
    }

    public function rejectionResoan()
    {

        return $this->belongsTo('App\Models\Reason', 'rejection_reason', 'id');
    }

    public function commentReport()
    {
        return $this->hasMany('App\Models\CommentReport', 'reservation_no', 'reservation_no');
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->forcedNullStrings) && $value === null)
            $value = "";
        else if (in_array($key, $this->forcedNullNumbers) && $value === null)
            $value = 0;

        return parent::setAttribute($key, $value);
    }

    public function getForMeAttribute()
    {
        if ($this->people_id == null || !is_numeric($this->people_id))
            return 1;
        return 0;
    }


    public function getBranchNameAttribute()
    {
        if ($this->provider_id == null || empty($this->provider_id))
            return '';
        else {
            return Provider::find($this->provider_id)->getTranslatedName();
        }
    }

    public function getBranchNoAttribute()
    {
        if ($this->provider_id == null || empty($this->provider_id))
            return '';
        else {
            return Provider::find($this->provider_id)->branch_no;
        }
    }

    public function getProviderRateAttribute($value)
    {
        if ($value == null)
            return '';
        return $value;
    }

    public function getServiceIdAttribute($value)
    {
        return $value == null ? 0 : $value;
    }

    public function getServiceRateAttribute($value)
    {
        return $value == null ? '' : $value;
    }

    public function getAddressAttribute($value)
    {
        return $value == null ? '' : $value;
    }

    public function scopeCurrent($query)
    {
        return $query->whereIn('approved', [0, 1]);
    }


    public function scopeToday($query)
    {
        return $query->whereDate('day_date', Carbon::today());
    }


    public function scopeSelection($query)
    {
        return $query->select('id', 'reservation_no', 'day_date', 'from_time', 'to_time', 'user_id', 'service_id', 'provider_id', 'payment_method_id', 'approved', 'price', 'rejection_reason');
    }

    public function scopeTommorow($query)
    {
        return $query->whereDate('day_date', Carbon::tomorrow());
    }


    public function scopeFinished($query)
    {
        return $query->/*where('day_date', '<', Carbon::now()->format('Y-m-d'))->*/ orwhere('approved', 2)->orwhere('approved', 3);
    }

    public function getPriceAttribute($value)
    {
        /* if ($this->payment_method_id != 1) {
             return "0";
         }*/
        return $value;
    }

    public function getMainproviderAttribute()
    {
        $branch_id = $this->provider_id;
        $main_id = Provider::where('id', $branch_id)->value('provider_id');
        $main_name = Provider::where('id', $main_id)->value('name_' . app()->getLocale());
        return $main_name;
    }


    public function getAdminValueFromReservationPriceTaxAttribute()
    {
        $this->makeVisible(['bill_total', 'application_percentage_bill', 'application_percentage']);
        if ($this->approved == 3) {
            $branch_id = $this->provider_id;    //branch of this reservation
            if (!$branch_id)
                return 0;
            $branch = Provider::where('id', $branch_id)->first();  // main provider of this reservation
            $provider = Provider::where('id', $branch->provider_id)->first();

            if (!$provider)
                return 0;
            $app_income = 0;
            $provider_tax_on_reservationPrice = $provider->application_percentage;    //3
            $provider_tax_on_reservationBill = $provider->application_percentage_bill;// 7

            $valueFromReservationPrice = (($this->price * $provider_tax_on_reservationPrice) / 100);// 3
            $app_income = $valueFromReservationPrice + (($valueFromReservationPrice * 5) / 100);  //3.15// admin value from reservation + 5% additional tax
            //   apply bill tax for reservation provider
            if ($this->bill_total > 0) {
                $valueFromReservationBill = (($this->bill_total * $provider_tax_on_reservationBill) / 100);// 35;
                $app_income += ($valueFromReservationBill + (($valueFromReservationBill * 5) / 100));// 36.75;
            }
            return $app_income;
        } else {
            return 0;
        }
    }

    //based on if reservation has application percentage or bill total or  paid coupon
    public function getReservationTotalAttribute()
    {
        $this->makeVisible(['bill_total', 'application_percentage_bill', 'application_percentage']);
        if ($this->approved == 3) {
            $branch_id = $this->provider_id;    //branch of this reservation
            if (!$branch_id)
                return 0;
            $branch = Provider::where('id', $branch_id)->first();  // main provider of this reservation
            $provider = Provider::where('id', $branch->provider_id)->first();

            $coupon = $this->coupon;
            if (!$provider)
                return 0;

            //reservation with bill_total or bill_total with insurance
            if ($this->bill_total > 0) {
                return $this->bill_total;
            } //reservation with coupon discount or paid coupon

            elseif (isset($coupon->id) && $coupon->id != null && $coupon->id != "") {
                if ($coupon->coupons_type_id == 2)
                    return $this->coupon->price;
                else {
                    $coupon_Price = $this->coupon->price;
                    $coupon_discount = $this->coupon->discount;
                    $value_discounted = ($coupon_Price * $coupon_discount) / 100;
                    return $coupon_Price - $value_discounted;
                }
                return $this->coupon->price;
            } //reservation with doctor price
            else
                return $this->price;
        } else {
            return 0;
        }
    }

    //for previous values in database in reservation table

    public function getLastDayDateAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }

    public function getLastFromTimeAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }

    public function getLastToTimeAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }

    public function getUserRejectionReasonAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }

    public function getRateDateAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }


    public function getRateCommentAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }

    public function getRejectedReasonNotesAttribute($val)
    {
        if ($val === null)
            return '';
        else
            return
                $val;
    }


    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id')->withDefault(["name" => ""]);
    }


    public function type()
    {
        return $this->belongsTo('App\Models\ServiceType', 'service_type', 'id');
    }

    public function scopeServiceSelection($query)
    {


        return $query->select(
            "id",
            "reservation_no",
            DB::raw("'' as offer_id"),
            DB::raw("'' as doctor_id"),
            "service_id",
            "day_date",
            "from_time",
            "to_time",
            "last_day_date",
            "last_from_time",
            "last_to_time",
            DB::raw("'' as doctor_rate"),
            "service_rate",
            'payment_type', 'custom_paid_price', 'remaining_price',
            "provider_rate",
            DB::raw("'' as offer_rate"),
            "paid",
            "approved",
            DB::raw("0 as use_insurance"),
            DB::raw("'' as promocode_id"),
            "provider_id",
            "branch_id",
            "price",
            "bill_total",
            "total_price",
            "rate_comment",
            "rate_date",
            "service_type",
            "latitude",
            "longitude",
            "hours_duration",
            DB::raw("'' as address"),
            "user_id",
            "payment_method_id"
        );
    }

    public function getPaymentTypeAttribute($val)
    {
        return $val !== null ? $val : "";
    }

    public function getCustomPaidPriceAttribute($val)
    {
        return $val !== null ? $val : "";
    }

    public function getRemainingPriceAttribute($val)
    {
        return $val !== null ? $val : "";
    }

}
