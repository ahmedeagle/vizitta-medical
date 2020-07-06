<?php

namespace App\Traits\Dashboard;

use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\Mix;
use App\Models\PromoCode;
use App\Models\Provider;
use App\Models\Reservation;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;

trait ReservationTrait
{
    use OdooTrait , SMSTrait;

    public function getReservationById($id)
    {
        return Reservation::find($id);
    }

    public function getAllReservations($status)
    {
        if ($status == 'delay') {
            $allowTime = 15;  // 15 min
            return Laratables::recordsOf(Reservation::class, function ($query) use ($allowTime) {
                return $query->where('approved', 0)->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)->orderBy('day_date', 'DESC')->orderBy('from_time', 'DESC')->select('*');
            });
        } elseif ($status == 'today_tomorrow') {

            return Laratables::recordsOf(Reservation::class, function ($query) {

                /*return $query->whereDate('day_date', Carbon::today())->orWhereDate('day_date', Carbon::tomorrow())
                    ->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');*/

                ########## Start to get all status except rejected in current day or tomorrow #########
                return $query->where('approved', '!=', 2)->where(function ($q) {
                    $q->whereDate('day_date', Carbon::today())->orWhereDate('day_date', Carbon::tomorrow());
                })->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');
                ########## End to get all status except rejected in current day or tomorrow #########

            });

        } elseif ($status == 'pending') {
            return Laratables::recordsOf(Reservation::class, function ($query) {
                return $query->where('approved', 0)->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');
            });
        } elseif ($status == 'approved') {
            return Laratables::recordsOf(Reservation::class, function ($query) {
                return $query->where('approved', 1)->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');
            });
        } elseif ($status == 'reject') {
            return Laratables::recordsOf(Reservation::class, function ($query) {
                return $query->where('approved', 2)->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');
            });
        } elseif ($status == 'completed') {
            return Laratables::recordsOf(Reservation::class, function ($query) {
                return $query->where('approved', 3)->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');
            });
        } else {
            return Laratables::recordsOf(Reservation::class, function ($query) { // all
                return $query->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->select('*');
            });
        }
    }

    public function getReservationByProviderId($provider_id, $request)
    {

        $provider = Provider::find($provider_id); // main Providers
        $branchsIds = $provider->providers()->pluck('id', 'name_ar')->toArray();
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $doctor_id = $request->doctor_id;
        $branch_id = $request->branch_id;
        $payment_method_id = $request->payment_method_id;
        $conditions = [];

        if (isset($from_date) && $from_date != '' && $from_date != null) {
            array_push($conditions, ['day_date', '>=', date('Y-m-d', strtotime($from_date))]);
        }
        if (isset($to_date) && $to_date != '' && $to_date != null) {
            array_push($conditions, ['day_date', '<=', date('Y-m-d', strtotime($from_date))]);
        }

        if (isset($doctor_id) && $doctor_id != '' && $doctor_id != null) {
            array_push($conditions, ['doctor_id', '=', $doctor_id]);
        }

        /*   if (isset($branch_id) && $branch_id != '' && $branch_id != null) {
                array_push($conditions,['provider_id', '=', $branch_id]);
           }*/

        if (isset($payment_method_id) && $payment_method_id != '' && $payment_method_id != null) {
            array_push($conditions, ['payment_method_id', '=', $payment_method_id]);
        }

        array_push($conditions, ['approved', '=', 3]);

        // return $conditions;
        if (!empty($conditions) && count($conditions) > 0) {
            return Laratables::recordsOf(Reservation::class, function ($query) use ($provider, $branchsIds, $conditions) {
                return $query->where($conditions)->whereIn('provider_id', $branchsIds)->orderBy('provider_id')->orderBy('created_at', 'DESC')->select('*');
            });
        }

        //not reachable
        return Laratables::recordsOf(Reservation::class, function ($query) use ($provider, $branchsIds) {
            return $query->whereIn('provider_id', $branchsIds)->orderBy('provider_id')->orderBy('created_at', 'DESC')->select('*');
        });

    }

    public function updateReservation($reservation, $request)
    {
        $reservation = $reservation->update($request->all());
        return $reservation;
    }


    public function calculateBalanceAdmin($provider, $paymentMethod_id, Reservation $reservation, $request)
    {
        // all this balance make by - minus because the payment only is  cash no visa untill now
        $manager = Mix::select('mobile', 'email', 'app_price')->first();
        $mainprov = Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id);
        $mainprov->makeVisible(['application_percentage_bill', 'application_percentage', 'application_percentage_bill_insurance', '']);
        //if there is bill  take app percentage from bill + reservation price
        $reservationBalance = 0;
        $discountType = '--';
        if (!is_numeric($mainprov->application_percentage_bill) || $mainprov->application_percentage_bill == 0) {
            $provider_has_bill = 0;
        } else {
            $provider_has_bill = 1;
        }

        if (!is_numeric($mainprov->application_percentage_bill_insurance) || $mainprov->application_percentage_bill_insurance == 0) {
            $provider_has_bill_insurance = 0;
        } else {
            $provider_has_bill_insurance = 1;
        }

        //if reservation without any coupon
        if ($reservation->use_insurance == 1 && $reservation->promocode_id == null) {
            $discountType = " فاتورة حجز نقدي بتأمين";
            $total_amount = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
            $MC_percentage = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
            $reservationBalanceBeforeTax = ($total_amount * $MC_percentage) / 100;
            $additional_tax_value = ($reservationBalanceBeforeTax * 5) / 100;
            $reservationBalance = ($reservationBalanceBeforeTax + $additional_tax_value);
        } elseif ($reservation->use_insurance == 0 && $reservation->promocode_id == null) {
            $discountType = " فاتورة حجز نقدي ";
            $total_amount = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
            $MC_percentage = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
            $reservationBalanceBeforeTax = ($total_amount * $MC_percentage) / 100;
            $additional_tax_value = ($reservationBalanceBeforeTax * 5) / 100;
            $reservationBalance = ($reservationBalanceBeforeTax + $additional_tax_value);
        }

        //if reservation with coupon
        if ($reservation->promocode_id != null) {
            if ($reservation->coupon->coupons_type_id == 1) {  //discount coupon
                //get  coupon total amount    step 1
                $totalCouponPrice = $reservation->coupon->price;   //1000
                $coupounDiscountPercentage = $reservation->coupon->discount;  //20
                $amountAfterDiscount = $coupounDiscountPercentage > 0 ? ($totalCouponPrice - (($totalCouponPrice * $coupounDiscountPercentage) / 100)) : $totalCouponPrice; //800
                $MC_percentage = $reservation->coupon->application_percentage;
                $medicalAmount = $MC_percentage > 0 ? ($amountAfterDiscount * $MC_percentage) / 100 : 0;
                $addationan_tax = ($medicalAmount * 5) / 100;
                //get amount after coupoun discount applied step 2
                //calculate admin percentage of step 2
                $discountType = " فاتورة حجز بكوبون خصم ";
                $reservationBalance = $medicalAmount + $addationan_tax;
            } else {   //prepaid coupon
                $offer_amount_WithoutVAT = $reservation->coupon->price;
                $MC_percentage = isset($reservation->coupon->paid_coupon_percentage) ? $reservation->coupon->paid_coupon_percentage : 0; //15
                $discountType = " فاتورة حجز بكوبون مدفوع  ";
                $amount = ($offer_amount_WithoutVAT * $MC_percentage) / 100;
                $addtional_tax = ($amount * 5) / 100;
                $reservationBalance = $amount + $addtional_tax;
            }
        }

        $provider = $reservation->provider;  // always get branch
        $provider->update([
            'balance' => $provider->balance - $reservationBalance,
        ]);
        $reservation->update([
            'discount_type' => $discountType,
        ]);
        /*  $manager->update([
              'balance' => $manager->unpaid_balance + $reservationBalance
          ]);*/

        return true;
    }

    public function changerReservationStatus($reservation, $status, $rejection_reason = null, $arrived = 0, $request = null)
    {

        if ($status != 3) {
            $reservation->update([
                'approved' => $status,
                'rejection_reason' => $rejection_reason,
            ]);
        }

        $provider = Provider::find($reservation->provider_id); // branch


        if ($status == 3) {  //complete Reservations

            if ($arrived == 1) {

                //calculate balance
                $reservation->update([
                    'approved' => 3,
                    'is_visit_doctor' => $arrived
                ]);

                $totalBill = 0;
                $comment = " نسبة ميدكال كول من كشف حجز نقدي";
                $invoice_type = 0;
                $mainProv = Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id);
                $provider->makeVisible(['device_token', 'application_percentage_bill_insurance', 'application_percentage_bill']);

                if (!is_numeric($mainProv->application_percentage_bill) || $mainProv->application_percentage_bill == 0) {
                    $provider_has_bill = 0;
                } else {
                    $provider_has_bill = 1;

                }

                if (!is_numeric($mainProv->application_percentage_bill_insurance) || $mainProv->application_percentage_bill_insurance == 0) {
                    $provider_has_bill_insurance = 0;
                } else {
                    $provider_has_bill_insurance = 1;
                }

                // get bill total only if discount apply to this provider  on bill and the reservation without coupons "bill case"
                if ($provider_has_bill == 1 && $reservation->promocode_id == null && $reservation->use_insurance == 0) {
                    if ($request->has('bill_total')) {
                        if ($request->bill_total <= 0) {
                            return response()->json(['status' => false, 'error' => __('messages.Must add Bill Total')], 200);
                        } else {
                            $totalBill = $request->bill_total;
                        }
                    } else {
                        return response()->json(['status' => false, 'error' => __('messages.Must add Bill Total')], 200);
                    }
                }

                // get bill total only if discount apply to this provider  on insurance_bill and the reservation without coupons "bill case"
                if ($provider_has_bill_insurance == 1 && $reservation->promocode_id == null && $reservation->use_insurance == 1) {
                    if ($request->has('bill_total')) {
                        if ($request->bill_total <= 0) {
                            return response()->json(['status' => false, 'error' => __('messages.Must add Bill Total')], 200);
                        } else {
                            $totalBill = $request->bill_total;
                        }
                    } else
                        return response()->json(['status' => false, 'error' => __('messages.Must add Bill Total')], 200);
                }
                $reservation->update([
                    'bill_total' => $request->bill_total,
                ]);

                $data = [];

                // Calculate the balance
                $this->calculateBalanceAdmin($provider, $reservation->payment_method_id, $reservation, $request);

                $manager = Mix::select('mobile', 'email', 'app_price')->first();
                $mainprov = Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id);
                $mainprov->makeVisible(['application_percentage_bill', 'application_percentage', 'application_percentage_bill_insurance']);

                // save odoo invoice with details  to odoo erp system on case cash "note uptill now only cash payment allowed "

                if ($reservation->use_insurance == 1 && $reservation->promocode_id == null) {
                    $data['payment_term'] = 5;
                    $data['sales_account'] = 580;
                    $comment = "  نسبة ميدكال كول من  فاتورة حجز نقدي بتأمين   ";
                    $invoice_type = 2;   // with insurance
                    $data['product_id'] = 4;
                    $data['total_amount'] = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
                    $data['MC_percentage'] = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
                    $data['invoice_type_id'] = $invoice_type;
                    $data['cost_center_id'] = 510;
                    $data['origin'] = $reservation->reservation_no;
                    $data['comment'] = $comment;
                    $data['sales_journal'] = 1;
                    $data['Receivables_account'] = 8;
                } elseif ($reservation->use_insurance == 0 && $reservation->promocode_id == null) {
                    $data['payment_term'] = 4; //edit
                    $data['sales_account'] = 19;
                    $comment = "  نسبة ميدكال كول من  فاتورة حجز نقدي عادية ";
                    $invoice_type = 1;   // without insurance
                    $data['product_id'] = 5;
                    $data['total_amount'] = ($provider_has_bill == 1 or $provider_has_bill_insurance == 1) && ($reservation->promocode_id == null) ? $request->bill_total : $reservation->price;
                    $data['MC_percentage'] = $reservation->use_insurance == 0 ? $mainprov->application_percentage_bill + $mainprov->application_percentage : $mainprov->application_percentage_bill_insurance + $mainprov->application_percentage;
                    $data['invoice_type_id'] = $invoice_type;
                    $data['cost_center_id'] = 510;
                    $data['origin'] = $reservation->reservation_no;
                    $data['comment'] = $comment;
                    $data['sales_journal'] = 1;
                    $data['Receivables_account'] = 8;
                }

                $branchOfReservation = $reservation->provider;

                if ($branchOfReservation->odoo_provider_id) {
                    $partner_id = $branchOfReservation->odoo_provider_id;
                    $data['partner_id'] = $partner_id;
                } else {
                    // if provider not has an account on odoo , create new account
                    $name = $mainProv->commercial_ar . ' - ' . $branchOfReservation->name_ar;
                    $odoo_provider_id = $this->saveProviderToOdoo($branchOfReservation->mobile, $name);
                    $branchOfReservation->update(['odoo_provider_id' => $odoo_provider_id]);
                    $partner_id = $odoo_provider_id;
                    $data['partner_id'] = $partner_id;
                }

                // if reservation is cash with insurance or without insurance
                     $odoo_invoice_id = $this->createInvoice_CashReservation($data);
                    $reservation->update(['odoo_invoice_id' => $odoo_invoice_id]);

            } else {
                $reservation->update([
                    'approved' => 2,
                    'is_visit_doctor' => 0
                ]);
            }

        }

        try {
            if ($provider && $reservation->user_id != null) {

                $name = 'name_' . app()->getLocale();
                if ($status == 1) {
                    $bodyProvider = __('messages.approved user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.approved your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                    $message = __('messages.your_reservation_has_been_accepted_from') . ' ( ' . "{$provider->provider->$name}" . ' ) ' .
                        __('messages.branch') . ' ( ' . " {$provider->getTranslatedName()} " . ' ) ' . __('messages.if_you_wish_to_change_reservations');

                } elseif($status == 2) {
                    $bodyProvider = __('messages.canceled user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                    $rejected_reason = 'name_' . app()->getLocale();
                    $message = __('messages.reject_reservations') . ' ( ' . "{$provider->provider->$name} - {$provider->getTranslatedName()}" . ' ) ' .
                        __('messages.because') . '( ' . "{$reservation->rejectionResoan->$rejected_reason}" . ' ) ' . __('messages.can_re_book');
                }elseif($status == 3) {
                    $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.complete your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                    $message = __('messages.your_reservation_has_been_complete_from') . ' ( ' . "{$provider->provider->$name}" . ' ) ' .
                        __('messages.branch') . ' ( ' . " {$provider->getTranslatedName()} " . ' ) ' . __('messages.if_you_wish_to_change_reservations');
                }

                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id));

                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user);

                if($status == 1 or $status == 2) {
                    //send mobile sms
                    $message = $bodyUser;
                    $this->sendSMS($reservation->user->mobile, $message);
                }
            }
        } catch (\Exception $exception) {

        }


        return response()->json(['status' => true, 'msg' => __('main.reservation_status_changed_successfully')]);
    }


    /* public function sendSMS($phone, $message)
     {

        $curl = new \App\Support\SMS\Curl();
         $username = "medicare";     // The user name of gateway
         $password = "Hh..36547820";          // the password of gateway
         $sender = "MedicalCaLL";
         $url = "http://www.jawalbsms.ws/api.php/sendsms?user=$username&pass=$password&to=$phone&message=$message&sender=$sender";
         $urlDiv = explode("?", $url);
         $result = $curl->_simple_call("post", $urlDiv[0], $urlDiv[1], array("TIMEOUT" => 3));
         return $result;


     }*/

    public static function getdayNameByDate($date)
    {
        $nameOfDay = date('l', strtotime($date));
        return $nameOfDay;
    }


}
