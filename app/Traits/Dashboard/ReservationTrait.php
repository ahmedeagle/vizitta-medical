<?php

namespace App\Traits\Dashboard;

use App\Models\Doctor;
use App\Models\Provider;
use App\Models\Reservation;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;

trait ReservationTrait
{
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

    public function changerReservationStatus($reservation, $status, $rejection_reason = null)
    {
        $reservation->update([
            'approved' => $status,
            'rejection_reason' => $rejection_reason
        ]);

        $provider = Provider::find($reservation->provider_id); // branch
        $provider->makeVisible(['device_token']);
        try {
            if ($provider && $reservation->user_id != null) {

                $name = 'name_' . app()->getLocale();
                if ($status == 1) {
                    $bodyProvider = __('messages.approved user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.approved your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                    $message = __('messages.your_reservation_has_been_accepted_from') . ' ( ' . "{$provider->provider->$name}" . ' ) ' .
                        __('messages.branch') . ' ( ' . " {$provider->getTranslatedName()} " . ' ) ' . __('messages.if_you_wish_to_change_reservations');

                } else {
                    $bodyProvider = __('messages.canceled user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                    $rejected_reason = 'name_' . app()->getLocale();
                    $message = __('messages.reject_reservations') . ' ( ' . "{$provider->provider->$name} - {$provider->getTranslatedName()}" . ' ) ' .
                        __('messages.because') . '( ' . "{$reservation->rejectionResoan->$rejected_reason}" . ' ) ' . __('messages.can_re_book');
                }

                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id));

                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user);

                //send mobile sms
//                $message = $bodyUser;

             //   $this->sendSMS($reservation->user->mobile, $message);
            }
        } catch (\Exception $exception) {

        }
        return $reservation;
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
