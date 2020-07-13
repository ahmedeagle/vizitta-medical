<?php

namespace App\Traits\Dashboard;

use App\Models\Doctor;
use App\Models\Mix;
use App\Models\Provider;
use App\Models\Reservation;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;
use DB;

trait GlobalOfferTrait
{
    use SMSTrait;

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
                $qq->whereNotNull('provider_id')
                    ->select('id', DB::raw('name_' . app()->getLocale() . ' as name'))
                    ->with(['provider' => function ($g) {
                        $g->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                            ->with(['type' => function ($gu) {
                                $gu->select('id', 'type_id', DB::raw('name_' . app()->getLocale() . ' as name'));
                            }]);
                    }
                    ]);
            }])->where('id', $reservation_id)
            ->first();
    }


    public function getReservationByNoWihRelation2($reservation_id)
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
            },
            'branch' => function ($q) {
                $q->select('id', 'name_' . app()->getLocale() . ' as name', 'provider_id');
                $q->with(['provider' => function ($qq) {
                    $qq->select('id', 'name_' . app()->getLocale() . ' as name', 'provider_id');
                }]);
            }, 'rejectionResoan' => function ($q) {
                $q->select('id', 'name_' . app()->getLocale() . ' as name');
            }])->where('id', $reservation_id)
            ->first();
    }

    public function changerReservationStatus($reservation, $status, $rejection_reason = null, $arrived = 0, $request = null)
    {
        if ($status != 3) {
            $reservation->update([
                'approved' => $status,
                'rejection_reason' => $rejection_reason
            ]);
        }


        $provider = Provider::find($reservation->provider_id); // branch
        $provider->makeVisible(['device_token']);

        $payment_method = $reservation->paymentMethod->id;   // 1- cash otherwise electronic
        $application_percentage_of_offer = $reservation->offer->application_percentage ? $reservation->offer->application_percentage : 0;
        $complete = $arrived;

        if ($status == 3) {  //complete Reservations

            if ($arrived == 1) {

                $reservation->update([
                    'approved' => 3,
                    'is_visit_doctor' => 1
                ]);

                if ($payment_method == 1 && $status == 3 && $complete == 1) {//1- cash reservation 3-complete reservation  1- user attend reservation
                    $totalBill = 0;
                    $comment = " نسبة ميدكال كول من كشف (عرض) حجز نقدي ";
                    $invoice_type = 0;
                    try {
                        $this->calculateOfferReservationBalanceForAdmin($application_percentage_of_offer, $reservation);
                    } catch (\Exception $ex) {
                    }
                }

                if ($payment_method != 1 && $status == 3 && $complete == 1) {//  visa reservation 3-complete reservation  1- user attend reservation
                    $totalBill = 0;
                    $comment = " نسبة ميدكال كول من كشف (عرض) حجز الكتروني ";
                    $invoice_type = 0;
                    try {
                        $this->calculateOfferReservationBalanceForAdmin($application_percentage_of_offer, $reservation);
                    } catch (\Exception $ex) {
                    }
                }

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

                } elseif ($status == 2) {
                    $bodyProvider = __('messages.canceled user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  getTranslatedName() } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";

                    $rejected_reason = 'name_' . app()->getLocale();
                    $message = __('messages.reject_reservations') . ' ( ' . "{$provider->provider->getTranslatedName()} - {$provider->getTranslatedName()}" . ' ) ' .
                        __('messages.because') . '( ' . "{$rejection_reason}" . ' ) ' . __('messages.can_re_book');
                } elseif ($status == 3) { // complete reservation
                    if ($complete == 1) { //when reservation complete and user arrived to branch
                        $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  getTranslatedName() } " . __('messages.branch') . " - {$provider->getTranslatedName()}  ";
                        $bodyUser = __('messages.complete your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()}  - ";
                    } else {
                        $bodyProvider = __('messages.canceled your reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  getTranslatedName() } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                        $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";
                    }
                    $message_res = $bodyUser;
                } else {
                    $bodyProvider = '';
                    $bodyUser = '';
                }

                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id));

                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user);

                //send mobile sms
                $message = $bodyUser;
                $this->sendSMS($reservation->user->mobile, $message);
            }
        } catch (\Exception $exception) {

        }
        return response()->json(['status' => true, 'msg' => __('main.reservation_status_changed_successfully')]);
    }

    public function calculateOfferReservationBalanceForAdmin($application_percentage_of_offer, Reservation $reservation)
    {
        if ($reservation->paymentMethod->id == 1) {//cash
            $discountType = " فاتورة حجز نقدي لعرض ";
            $total_amount = $reservation->offer->price_after_discount; // اجمالي اكشف
            $MC_percentage = $application_percentage_of_offer;
            $reservationBalanceBeforeAdditionalTax = ($total_amount * $MC_percentage) / 100;
            $additional_tax_value = ($reservationBalanceBeforeAdditionalTax * 5) / 100;
            $reservationBalance = ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);

            $provider = $reservation->provider;  // always get branch
            $provider->update([
                'balance' => $provider->balance - $reservationBalance,
            ]);
            $reservation->update([
                'discount_type' => $discountType,
            ]);
            $manager = $this->getAppInfo();
            $manager->update([
                'balance' => $manager->unpaid_balance + $reservationBalance
            ]);
        } else {

            $discountType = " فاتورة حجز الكتروني لعرض ";
            $total_amount = $reservation->offer->price_after_discount;
            $MC_percentage = $application_percentage_of_offer;
            $reservationBalanceBeforeAdditionalTax = ($total_amount * $MC_percentage) / 100;  //20 ريال
            $additional_tax_value = ($reservationBalanceBeforeAdditionalTax * 5) / 100;   //2
            $reservationBalance = $total_amount - ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);

            $provider = $reservation->provider;  // always get branch
            $provider->update([
                'balance' => $provider->balance + $reservationBalance,
            ]);
            $reservation->update([
                'discount_type' => $discountType,
            ]);
            $manager = $this->getAppInfo();
            $manager->update([
                'balance' => $manager->unpaid_balance + $reservationBalance
            ]);

        }

        return true;
    }
}
