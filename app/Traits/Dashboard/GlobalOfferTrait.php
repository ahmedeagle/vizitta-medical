<?php

namespace App\Traits\Dashboard;

use App\Models\Doctor;
use App\Models\Provider;
use App\Models\Reservation;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;
use DB;
trait GlobalOfferTrait
{
    public function getReservationById($id)
    {
        return Reservation::find($id);
    }

    public function getReservationByNoWihRelation($reservation_id)
    {

        return Reservation::with(['commentReport', 'offer' => function ($g) {
            $g->select('id', DB::raw('title_' . app()->getLocale() . ' as title'), 'photo');
        }, 'rejectionResoan' => function ($rs) {
            $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
            , 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_company_id', 'insurance_image', 'mobile')->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }, 'provider' => function ($qq) {
                $qq->whereNotNull('provider_id')->select('id', DB::raw('name_' . app()->getLocale() . ' as name'))
                    ->with(['provider' => function ($g) {
                        $g->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                            ->with(['type' => function ($gu) {
                                $gu->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                    }]);
            }])->where('id',$reservation_id)
            ->first();
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

                $this->sendSMS($reservation->user->mobile, $message);
            }
        } catch (\Exception $exception) {

        }
        return $reservation;
    }

}
