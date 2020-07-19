<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Reservation extends Model
{

    protected $table = 'reservations';
    public $timestamps = true;
    protected $forcedNullStrings = ['transaction_id', 'bill_photo', 'reservation_no', 'rejection_reason', 'price', 'provider_rate', 'doctor_rate', 'rate_comment', 'bill_total', 'last_day_date', 'last_from_time', 'last_to_time', 'user_rejection_reason', 'offer_rate', 'address'];
    protected $forcedNullNumbers = [];

    protected $fillable = ['application_balance_value', 'reservation_no', 'transaction_id', 'user_id', 'doctor_id', 'day_date', 'from_time', 'to_time', 'payment_method_id', 'paid',
        'approved', 'use_insurance', 'promocode_id', 'order', 'provider_id', 'doctor_rate', 'provider_rate', 'rate_comment', 'rate_date', 'rejection_reason', 'price', 'people_id', 'is_visit_doctor', 'bill_total', 'discount_type',
        'bill_photo', 'odoo_invoice_id', 'odoo_offer_id', 'last_day_date', 'last_from_time', 'last_to_time', 'user_rejection_reason',
        'offer_id', 'offer_rate', 'address', 'payment_type', 'custom_paid_price', 'remaining_price', 'created_at'];

    protected $hidden = ['bill_photo', 'updated_at', 'user_id', 'payment_method_id', 'people_id', 'discount_type', 'odoo_invoice_id', 'odoo_offer_id'];
    protected $appends = ['for_me', 'branch_name', 'branch_no', 'is_reported', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'reservation_total'];

    public static function laratablesCustomAction($reservation)
    {
        return view('reservation.actions', compact('reservation'))->render();
    }


    // get app income from reservation price discount + bill discount if exists
    public static function laratablesCustomAppIncome($reservation)
    {
        $reservation->makeVisible(['bill_total', 'application_percentage_bill', 'application_percentage']);

        $branch = $reservation->provider;
        if (!$branch)
            return 0;
        $provider = Provider::find($branch->provider_id);
        if (!$provider)
            return 0;

        $app_income = 0;
        $provider_tax_on_reservationPrice = $provider->application_percentage;
        $provider_tax_on_reservationBill = $provider->application_percentage_bill;
        $app_income = ($reservation->price * $provider_tax_on_reservationPrice) / 100;
        //   apply bill tax for reservation provider
        if ($reservation->bill_total > 0) {
            $app_income += ($reservation->bill_total * $provider->$provider_tax_on_reservationBill) / 100;;
        }

        return $app_income;
    }


    public static function laratablesCustomActioncomments($reservation)
    {
        return view('reservation.commentsactions', compact('reservation'))->render();
    }


    public function getIsReportedAttribute()
    {
        if (isset($this->commentReport) && isset($this->commentReport->id))
            return '1';
        else
            return '';
    }

    public function laratablesApproved()
    {
        if ($this->approved == 1)
            $result = 'موافق عليه';
        elseif ($this->approved == 2 && $this->rejection_reason != 0 && ($this->rejection_reason != null or $this->rejection_reason != ""))
            $result = 'مرفوض';
        elseif ($this->approved == 3 && $this->is_visit_doctor == 1)
            $result = ' مكتمل بزياره العميل';
        elseif ($this->approved == 2 && ($this->rejection_reason == null or $this->rejection_reason = ''))
            $result = ' مكتمل بعدم زياره العميل';
        elseif ($this->approved == 4)
            $result = 'مشغول';
        elseif ($this->approved == 5)
            $result = 'مرفوض بواسطه المستخدم ';
        else
            $result = 'معلق';
        return $result;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id')->withDefault(["name" => ""]);
    }

    public function promoCode()
    {
        return $this->belongsTo('App\Models\PromoCode', 'promocode_id')->withDefault(["name" => ""]);
    }

    public function coupon()
    {
        return $this->belongsTo('App\Models\PromoCode', 'promocode_id')->withDefault(["title" => ""]);
    }

    public function doctor()
    {
        return $this->belongsTo('App\Models\Doctor', 'doctor_id')->withDefault(["name" => ""]);
    }


    public function offer()
    {
        return $this->belongsTo('App\Models\Offer', 'offer_id')->withDefault(["name" => ""]);
    }


    public function mainProvider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id', 'provider_id')->withDefault(["name" => ""]);
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function doctorInfo()
    {

        return $this->belongsTo('App\Models\doctor', 'doctor_id')->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), 'price', 'photo', 'rate', 'specification_id');
    }

    public function branchId()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id', 'address', 'street', 'latitude', 'longitude');
    }

    public function records()
    {
        return $this->hasMany('App\Models\UserRecord', 'reservation_no')->withDefault(["name" => ""]);
    }

    public function branch()
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id')->withDefault(["name" => ""]);
    }

    public function paymentMethod()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_method_id')->withDefault(["name" => ""]);
    }

    public function people()
    {
        return $this->belongsTo('App\Models\People', 'people_id')->withDefault(["name" => ""]);
    }

    public function getApproved()
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

    public function getDoctorRateAttribute($value)
    {
        if ($value == null)
            return '';
        return $value;
    }

    public function getDoctorIdAttribute($value)
    {
        return $value == null ? 0 : $value;
    }

    public function getOfferIdAttribute($value)
    {
        return $value == null ? 0 : $value;
    }

    public function getOfferRateAttribute($value)
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
        return $query->select('id', 'reservation_no', 'day_date', 'from_time', 'to_time', 'user_id', 'doctor_id', 'provider_id', 'payment_method_id', 'approved', 'price', 'bill_total', 'discount_type', 'rejection_reason', 'payment_type', 'custom_paid_price', 'remaining_price');
    }

    public function scopeOfferSelection($query)
    {
        return $query->select('id', 'reservation_no', 'transaction_id', 'day_date', 'from_time', 'to_time', 'user_id', 'offer_id', 'provider_id', 'payment_method_id', 'approved', 'price', 'bill_total', 'payment_type', 'custom_paid_price', 'remaining_price');
    }

    public function scopeTommorow($query)
    {
        return $query->whereDate('day_date', Carbon::tomorrow());
    }


    public function scopeFinished($query)
    {
        return $query->/*where('day_date', '<', Carbon::now()->format('Y-m-d'))->*/ where(function ($q) {
            $q->where(function ($q) {
                $q->where('approved', 2)->orwhere('approved', 5)->orwhere('approved', 3);
            });
        });
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

    public function scopeDoctorSelection($query)
    {
        return $query->select('id',
            "reservation_no",
            "offer_id",
            "doctor_id",
            DB::raw("'' as service_id"),
            "day_date",
            "from_time",
            "to_time",
            "last_day_date",
            "last_from_time",
            "last_to_time",
            "doctor_rate",
            DB::raw("'' as service_rate"),
            'payment_type', 'custom_paid_price', 'remaining_price',
            "provider_rate",
            "offer_rate",
            "paid",
            "approved",
            "use_insurance",
            "promocode_id",
            "provider_id",
            DB::raw("'' as branch_id"),
            "price",
            "bill_total",
            DB::raw("'' as total_price"),
            "rate_comment",
            "rate_date",
            DB::raw("'' as service_type"),
            DB::raw("'' as latitude"),
            DB::raw("'' as longitude"),
            DB::raw("'' as hours_duration"),
            "address",
            "user_id",
            "payment_method_id"

        );
    }

    public
    function scopeOfferReservationSelection($query)
    {
        return $query->select(
            "id",
            "reservation_no",
            "offer_id",
            "doctor_id",
            DB::raw("'' as service_id"),
            "day_date",
            "from_time",
            "to_time",
            "last_day_date",
            "last_from_time",
            "last_to_time",
            "doctor_rate",
            DB::raw("'' as service_rate"),
            'payment_type', 'custom_paid_price', 'remaining_price',
            "provider_rate",
            "offer_rate",
            "paid",
            "approved",
            "use_insurance",
            "promocode_id",
            "provider_id",
            DB::raw("'' as branch_id"),
            "price",
            "bill_total",
            DB::raw("'' as total_price"),
            "rate_comment",
            "rate_date",
            DB::raw("'' as service_type"),
            DB::raw("'' as latitude"),
            DB::raw("'' as longitude"),
            DB::raw("'' as hours_duration"),
            "address",
            "user_id",
            "payment_method_id"
        );
    }


    //for union only
    public function type()
    {
        return $this->belongsTo('App\Models\ServiceType', 'service_type', 'id')->withDefault(["name" => ""]);
    }

    public function service()
    {
        return $this->belongsTo('App\Models\Service', 'service_id')->withDefault(["name" => ""]);
    }

    public function getPaymentTypeAttribute($val)
    {
        return $val !== null ? $val : "";
    }

    public function getCustomPaidPriceAttribute($val)
    {
        return $val !== null ? $val : "0";
    }

    public function getRemainingPriceAttribute($val)
    {
        return $val !== null ? $val : "0";
    }
}
