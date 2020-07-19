<?php

namespace App\Http\Controllers;

use App\Mail\AcceptReservationMail;
use App\Mail\NewReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\ExtraServices;
use App\Models\GeneralNotification;
use App\Models\InsuranceCompanyDoctor;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PromoCode;
use App\Models\People;
use App\Models\Service;
use App\Models\ServiceReservation;
use App\Models\User;
use App\Models\Provider;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Traits\DoctorTrait;
use App\Traits\OdooTrait;
use App\Traits\PromoCodeTrait;
use App\Traits\ServiceTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalTrait;
use Illuminate\Support\Facades\Mail;
use Validator;
use DB;
use DateTime;
use function foo\func;

class ServiceController extends Controller
{
    use GlobalTrait, ServiceTrait, PromoCodeTrait, OdooTrait, SMSTrait;

    public function index(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "category_id" => "required",
                "branch_id" => "required|exists:providers,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $services = Service::query();
            $queryStr = $request->queryStr;
            $category_id = $request->category_id;
            $branch_id = $request->branch_id;

            $services = $services->active()->with(['specification' => function ($q1) {
                $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($q2) {
                $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            }, 'provider' => function ($q2) {
                $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'types' => function ($q3) {
                $q3->select('services_type.id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethods'
            ])
                ->where('branch_id', $branch_id);

            if ($category_id != 0)
                $services = $services->where('specification_id', $category_id);


            if (isset($request->queryStr)) {
                $services = $services->where(function ($q4) use ($queryStr) {
                    $q4->where('title_en', 'LIKE', '%' . trim($queryStr) . '%')
                        ->orWhere('title_ar', 'LIKE', '%' . trim($queryStr) . '%');
                });
            }

            $services = $services
                ->select(
                    'id',
                    DB::raw('title_' . $this->getCurrentLang() . ' as title'),
                    DB::raw('information_' . $this->getCurrentLang() . ' as information')
                    , 'specification_id',
                    'provider_id',
                    'branch_id',
                    'rate',
                    // 'price',
                    'clinic_price',
                    'home_price',
                    'home_price_duration',
                    'clinic_price_duration',
                    'status',
                    'reservation_period as clinic_reservation_period'
                )->paginate(PAGINATION_COUNT);


            if (count($services) > 0) {
                foreach ($services as $key => $service) {
                    $service->time = "";
                    $days = $service->times;
                    $num_of_rates = ServiceReservation::where('service_id', $service->id)
                        ->Where('service_rate', '!=', null)
                        ->Where('service_rate', '!=', 0)
                        ->Where('provider_rate', '!=', null)
                        ->Where('provider_rate', '!=', 0)
                        ->count();

                    $service->num_of_rates = $num_of_rates;
                }
                $total_count = $services->total();
                $per_page = PAGINATION_COUNT;
                $services->getCollection()->each(function ($service) {
                    $service->makeHidden(['available_time', 'provider_id', 'branch_id', 'hide', 'clinic_reservation_period', 'time']);
                    return $service;
                });

                $services = json_decode($services->toJson());
                $servicesJson = new \stdClass();
                $servicesJson->current_page = $services->current_page;
                $servicesJson->total_pages = $services->last_page;
                $servicesJson->total_count = $total_count;
                $servicesJson->per_page = $per_page;
                $servicesJson->data = $services->data;

                return $this->returnData('services', $servicesJson);
            }

            return $this->returnError('E001', trans('messages.No data founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function indexV2(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "category_id" => "required",
                "branch_id" => "required|exists:providers,id",
                "type" => "required|in:1,2"  // 1-home   2-clinic
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $services = Service::query();
            $queryStr = $request->queryStr;
            $category_id = $request->category_id;
            $branch_id = $request->branch_id;
            $type = $request->type;

            $services = $services->active()->with(['specification' => function ($q1) {
                $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($q2) {
                $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            }, 'provider' => function ($q2) {
                $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'types' => function ($q3) {
                $q3->select('services_type.id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethods'
            ])
                ->where('branch_id', $branch_id)
                ->whereHas('types', function ($q3) use ($type) {
                    $q3->where('services_type.id', $type);
                });

            if ($category_id != 0)
                $services = $services->where('specification_id', $category_id);


            if (isset($request->queryStr)) {
                $services = $services->where(function ($q4) use ($queryStr) {
                    $q4->where('title_en', 'LIKE', '%' . trim($queryStr) . '%')
                        ->orWhere('title_ar', 'LIKE', '%' . trim($queryStr) . '%');
                });
            }

            $services = $services
                ->select(
                    'id',
                    DB::raw('title_' . $this->getCurrentLang() . ' as title'),
                    DB::raw('information_' . $this->getCurrentLang() . ' as information')
                    , 'specification_id',
                    'provider_id',
                    'branch_id',
                    'rate',
                    // 'price',
                    'clinic_price',
                    'home_price',
                    'home_price_duration',
                    'clinic_price_duration',
                    'status',
                    'reservation_period as clinic_reservation_period'
                )->paginate(PAGINATION_COUNT);


            if (count($services) > 0) {
                foreach ($services as $key => $service) {
                    $service->time = "";
                    $days = $service->times;
                    $num_of_rates = ServiceReservation::where('service_id', $service->id)
                        ->Where('service_rate', '!=', null)
                        ->Where('service_rate', '!=', 0)
                        ->Where('provider_rate', '!=', null)
                        ->Where('provider_rate', '!=', 0)
                        ->count();

                    $service->num_of_rates = $num_of_rates;
                }
                $total_count = $services->total();
                $per_page = PAGINATION_COUNT;
                $services->getCollection()->each(function ($service) {
                    $service->makeHidden(['available_time', 'provider_id', 'branch_id', 'hide', 'clinic_reservation_period', 'time']);
                    return $service;
                });

                $services = json_decode($services->toJson());
                $servicesJson = new \stdClass();
                $servicesJson->current_page = $services->current_page;
                $servicesJson->total_pages = $services->last_page;
                $servicesJson->total_count = $total_count;
                $servicesJson->per_page = $per_page;
                $servicesJson->data = $services->data;

                return $this->returnData('services', $servicesJson);
            }

            return $this->returnError('E001', trans('messages.No data founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getServiceRates(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $service = Service::find($request->service_id);

            if ($service == null)
                return $this->returnError('E001', trans('messages.Service not found'));

            $reservations = $service->
            reservations()
                ->with(['user' => function ($q) {
                    $q->select('id', 'name', 'photo');
                }, 'provider' => function ($qq) {
                    $qq->select('id', 'name_' . app()->getLocale() . ' as name', 'logo');
                }])
                ->select('id', 'user_id', 'service_rate', 'provider_rate', 'rate_date', 'rate_comment', 'provider_id', 'reservation_no')
                ->WhereNotNull('provider_rate')
                ->Where('provider_rate', '!=', 0)
                ->WhereNotNull('service_rate')
                ->Where('service_rate', '!=', 0)
                ->paginate(10);

            if (count($reservations->toArray()) > 0) {

                $reservations->getCollection()->each(function ($reservation) {
                    $reservation->makeHidden(['provider_id', 'for_me', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'reservation_total', 'rejected_reason_type']);
                    return $reservation;
                });

                $num_of_rates = ServiceReservation::where('service_id', $request->service_id)
                    ->WhereNotNull('provider_rate')
                    ->Where('provider_rate', '!=', 0)
                    ->WhereNotNull('service_rate')
                    ->Where('service_rate', '!=', 0)
                    ->count('service_rate');

                $num_of_visitors = ServiceReservation::where('service_id', $request->service_id)
                    ->count();


                $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $rateJson = new \stdClass();
                $rateJson->current_page = $reservations->current_page;
                $rateJson->total_pages = $reservations->last_page;
                $rateJson->total_count = $total_count;
                $rateJson->per_page = PAGINATION_COUNT;
                $rateJson->general_rate = $service->rate;
                $rateJson->num_of_rates = $num_of_rates;
                $rateJson->num_of_visitors = $num_of_visitors;
                $rateJson->data = $reservations->data;
                return $this->returnData('rates', $rateJson);
            }

            $this->returnError('E001', trans('messages.No rates founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    protected
    function getRandomString($length)
    {
        $characters = '0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = Reservation::where('reservation_no', $string)->first();
        if ($chkCode) {
            $this->getRandomString(8);
        }
        return $string;
    }

    public
    function ChangeReservationStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|exists:service_reservations,id",
                "status" => "required|in:1,2,3" //1->approved 2->cancelled 3 ->complete
            ]);

            if ($request->status == 2) {
                $validator->addRules([
                    'rejected_reason_id' => 'required|string',
                    'rejected_reason_notes' => 'sometimes|nullable|string',
                ]);
            }
            if ($request->status == 3) {
                $validator->addRules([
                    "arrived" => "required|in:0,1",
                ]);

            }

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            \Illuminate\Support\Facades\DB::beginTransaction();
            $provider = $this->auth('provider-api');

            $provider->makeVisible(['application_percentage_bill']);

            $reservation = $this->getServicesReservationByNo($request->reservation_id, $provider->id);

            if (!$reservation)
                return $this->returnError('D000', trans('messages.No reservation with this number'));

            if ($reservation->approved == 1 && $request->status == 1)
                return $this->returnError('E001', trans('messages.Reservation already approved'));

            if ($reservation->approved == 2 && $request->status == 2)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            if ($reservation->approved == 3 && $request->status == 3)
                return $this->returnError('E001', trans('messages.Reservation already Completed'));

            if ($reservation->approved == 2 && $request->status == 3)
                return $this->returnError('E001', trans('messages.Reservation already rejected'));

            if ($reservation->approved == 0 && $request->status == 3)
                return $this->returnError('E001', trans('messages.Reservation must be approved first'));

         /*   if ($request->status == 1) {
                if (strtotime($reservation->day_date) < strtotime(Carbon::now()->format('Y-m-d')) ||
                    (strtotime($reservation->day_date) == strtotime(Carbon::now()->format('Y-m-d')) &&
                        strtotime($reservation->to_time) < strtotime(Carbon::now()->format('H:i:s')))
                ) {

                    return $this->returnError('E001', trans("messages.You can't take action to a reservation passed"));
                }
            }*/

            if ($request->status == 1) {
                /* $ReservationsNeedToClosed = $this->checkIfThereReservationsNeedToClosed($request->reservation_no, $provider->id);
                 if ($ReservationsNeedToClosed > 0) {
                     return $this->returnError('AM01', trans("messages.there are reservations need to be closed first"));
                 }*/
            }

            $complete = (isset($request->arrived) && $request->arrived == 1) ? 1 : 0;

            DB::commit();

            try {

                //here we check if user visited in home  AND  has extra services must calculate them
                if ($request->status == 3 && $request->arrived == 1 && $reservation->service_type == 1) {
                    if (!isset($request->has_extra_services) or ($request->has_extra_services != 0 && $request->has_extra_services != 1)) {
                        return $this->returnError('E001', trans('messages.must enter extra services status'));
                    }

                    if ($request->has_extra_services == 1 && (!isset($request->extra_services) or empty($request->extra_services) != 0 or is_null($request->extra_services))) {
                        return $this->returnError('E001', trans('messages.must enter extra services'));
                    }

                    if ($request->has_extra_services == 1) {
                        if (isset($request->extra_services) && count($request->extra_services) > 0) {
                            $extra_services_array = [];
                            foreach ($request->extra_services as $extra) {
                                $extra_service = new ExtraServices();
                                $extra_service->name = $extra['name'] ;
                                $extra_service->price = $extra['price'];
                                $extra_service->save();
                                array_push($extra_services_array, $extra_service);
                            }
                            $reservation->extraServices()->saveMany($extra_services_array);
                        }
                    }
                }

                if ($request->status == 3) {
                    $complete = $request->arrived;

                    if ($complete == 1) {
                        //calculate balance
                        $reservation->update([
                            'approved' => 3,
                            'is_visit_doctor' => $complete
                        ]);
                    } else {
                        //calculate balance
                        $reservation->update([
                            'approved' => 2,
                            'is_visit_doctor' => $complete
                        ]);

                    }
                } else {
                    $reservation->update([
                        'approved' => $request->status, //approve reservation
                    ]);
                }
                ########################## Start calculate balance #################################

                $payment_method = $reservation->paymentMethod->id;   // 1- cash otherwise electronic
                $application_percentage_of_bill = $reservation->provider->application_percentage_bill ? $reservation->provider->application_percentage_bill : 0;

                if ($payment_method == 1 && $request->status == 3 && $complete == 1) {//1- cash reservation 3-complete reservation  1- user attend reservation
                    $totalBill = 0;
                    $comment = " نسبة ميدكال كول من كشف (خدمة) حجز نقدي ";
                    $invoice_type = 0;
                    try {
                         $this->calculateServiceReservationBalance($application_percentage_of_bill, $reservation, $request);
                    } catch (\Exception $ex) {
                        return $ex;
                    }
                }


                if ($payment_method != 1 && $request->status == 3 && $complete == 1) {//  visa reservation 3-complete reservation  1- user attend reservation
                    $totalBill = 0;
                    $comment = " نسبة ميدكال كول من كشف (خدمة) حجز الكتروني ";
                    $invoice_type = 0;
                    try {
                         $this->calculateServiceReservationBalance($application_percentage_of_bill, $reservation,$request);
                    } catch (\Exception $ex) {

                        return $ex;
                    }
                }

                ########################## End calculate balance #################################

                $name = 'name_' . app()->getLocale();

                $message_res = '';
                if ($request->status == 1) {  //approve
                    $message_res = __('messages.Reservation approved successfully');
                    $bodyProvider = __('messages.approved user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.approved your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";
                } elseif ($request->status == 2) {  //cancelled
                    $message_res = __('messages.Reservation rejected successfully');
                    $bodyProvider = __('messages.canceled user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                    $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";
                } elseif ($request->status == 3) { // complete reservation
                    if ($complete == 1) { //when reservation complete and user arrived to branch
                        $bodyProvider = __('messages.complete user reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()}  ";
                        $bodyUser = __('messages.complete your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()}  - ";
                    } else {
                        $bodyProvider = __('messages.canceled your reservation') . "  {$reservation->user->name}   " . __('messages.in') . " {$provider -> provider ->  $name } " . __('messages.branch') . " - {$provider->getTranslatedName()} ";
                        $bodyUser = __('messages.canceled your reservation') . " " . "{$provider -> provider ->  $name } " . __('messages.branch') . "  - {$provider->getTranslatedName()} ";
                    }
                    $message_res = $bodyUser;
                } else {
                    $bodyProvider = '';
                    $bodyUser = '';
                }
                //send push notification
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyProvider]))->sendProvider(Provider::find($provider->provider_id == null ? $provider->id : $provider->provider_id));
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => $bodyUser]))->sendUser($reservation->user);

                //send mobile sms
                $message = $bodyUser;
                $this->sendSMS($reservation->user->mobile, $message);

            } catch (\Exception $ex) {
            }
            return $this->returnSuccessMessage($message_res);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    protected function calculateServiceReservationBalance($application_percentage_of_bill, $reservation, $request)
    {
//        $reservation->service_type == 1 ### 1 = home & 2 = clinic

        if ($reservation->service_type == 2  ) {//clinic services only cache paid allowed with bill percentage with out additional services

            if($reservation -> price > 0 )// not free sevice
            {
                $total_amount = floatval($reservation->price);
                $MC_percentage = $application_percentage_of_bill;
                $reservationBalanceBeforeAdditionalTax = ($total_amount * $MC_percentage) / 100;
                $additional_tax_value = ($reservationBalanceBeforeAdditionalTax * env('ADDITIONAL_TAX', '5')) / 100;

                if ($reservation->paymentMethod->id == 1) {//cash
                    $discountType = " فاتورة حجز نقدي لخدمة عياده ";
                    $reservationBalance = ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);

                    $branch = $reservation->branch;  // always get branch
                    $branch->update([
                        'balance' => $branch->balance - $reservationBalance,
                    ]);
                    $reservation->update([
                        'discount_type' => $discountType,
                        'application_balance_value' => -$reservationBalance
                    ]);
                }
            }
        } else {  // home services

            $total_amount = floatval($reservation->price);
            $MC_percentage = $application_percentage_of_bill;
            $reservationBalanceBeforeAdditionalTax = ($total_amount * $MC_percentage) / 100;
            $additional_tax_value = ($reservationBalanceBeforeAdditionalTax * env('ADDITIONAL_TAX', '5')) / 100;
            $branch = $reservation->branch;  // always get branch

            //cash with/without additional services
            if ($reservation->paymentMethod->id == 1) {//cash
                $discountType = " فاتورة حجز نقدي لخدمة منزلية ";

                // cash extra services balance


                $ExtraReservationBalanceBeforeAdditionalTax =0;
                $ExtraAdditional_tax_value =0;
                if (isset($reservation->extraServices) && count($reservation->extraServices) > 0) {
                    $priceOfExtraReservation = $reservation->extraServices()->sum('price');
                    $extra_total_amount = floatval($priceOfExtraReservation);
                    $Extra_MC_percentage = $application_percentage_of_bill;
                    $ExtraReservationBalanceBeforeAdditionalTax = ($extra_total_amount * $Extra_MC_percentage) / 100;
                    $ExtraAdditional_tax_value = ($ExtraReservationBalanceBeforeAdditionalTax * env('ADDITIONAL_TAX', '5')) / 100;
                }

                 $reservationBalance = ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);
                $branch->update([
                    'balance' => $branch->balance - ($reservationBalance + $ExtraReservationBalanceBeforeAdditionalTax + $ExtraAdditional_tax_value)
                ]);

                $reservation->update([
                    'discount_type' => $discountType,
                    'application_balance_value' => -($reservationBalance + $ExtraReservationBalanceBeforeAdditionalTax + $ExtraAdditional_tax_value)
                ]);

            } //electronic with visa
            else {
                if ($reservation->payment_type == 'full') {
                    $discountType = " فاتورة حجز الكتروني لخدمة منزلية دفع كامل  ";

                    //check if there are extra services
                    $ExtraReservationBalanceBeforeAdditionalTax = 0;
                    $ExtraAdditional_tax_value = 0;
                    if (isset($reservation->extraServices) && count($reservation->extraServices) > 0) {
                        $priceOfExtraReservation = $reservation->extraServices()->sum('price');
                        $extra_total_amount = floatval($priceOfExtraReservation);
                        $Extra_MC_percentage = $application_percentage_of_bill;
                        $ExtraReservationBalanceBeforeAdditionalTax = ($extra_total_amount * $Extra_MC_percentage) / 100;
                        $ExtraAdditional_tax_value = ($ExtraReservationBalanceBeforeAdditionalTax * env('ADDITIONAL_TAX', '5')) / 100;
                    }

                    $reservationBalance =
                        $total_amount -
                        ($reservationBalanceBeforeAdditionalTax
                            + $additional_tax_value
                            + $ExtraReservationBalanceBeforeAdditionalTax
                            + $ExtraAdditional_tax_value
                        );

                    $branch->update([
                        'balance' => $branch->balance + $reservationBalance,
                    ]);

                    $reservation->update([
                        'discount_type' => $discountType,
                        'application_balance_value' => $reservationBalance
                    ]);

                } elseif ($reservation->payment_type == 'custom') {
                    $discountType = " فاتورة حجز الكتروني لخدمة منزلية دفع جزئي ";
                    $reservationBalance = $reservation->custom_paid_price;

                    $ExtraReservationBalanceBeforeAdditionalTax = 0;
                    $ExtraAdditional_tax_value = 0;

                    if (isset($reservation->extraServices) && count($reservation->extraServices) > 0) {
                        $priceOfExtraReservation = $reservation->extraServices()->sum('price');
                        $extra_total_amount = floatval($priceOfExtraReservation);
                        $Extra_MC_percentage = $application_percentage_of_bill;
                        $ExtraReservationBalanceBeforeAdditionalTax = ($extra_total_amount * $Extra_MC_percentage) / 100;
                        $ExtraAdditional_tax_value = ($ExtraReservationBalanceBeforeAdditionalTax * env('ADDITIONAL_TAX', '5')) / 100;
                    }

                    $ExtrareservationBalance = $ExtraReservationBalanceBeforeAdditionalTax + $ExtraAdditional_tax_value;

                    $branch->update([
                        'balance' => $branch->balance - $ExtrareservationBalance,
                    ]);

                    $reservation->update([
                        'discount_type' => $discountType,
                        'application_balance_value' => - $ExtrareservationBalance
                    ]);

                }
            }
        }
        return true;

    }

    public function getReservationDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|exists:service_reservations,id",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $provider = $this->auth('provider-api');
            if (!$provider)
                return $this->returnError('D000', __('messages.provider not found'));

            $reservation_details = $this->getReservationByReservationId($request->reservation_id, $provider);

            if ($reservation_details) {
                $main_provider = Provider::where('id', $reservation_details->provider['provider_id'])
                    ->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'))
                    ->first();
                $reservation_details->main_provider = $main_provider ? $main_provider : '';
                $reservation_details->makeHidden(['order', 'rejected_reason_type', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_notes', 'rejected_reason_id', 'bill_total', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);
                return $this->returnData('reservation_details', $reservation_details);
            } else
                return $this->returnError('E001', trans('messages.No reservations founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function checkIfThereReservationsNeedToClosed($no, $provider_id, $list = true)
    {
        $need_To_finish = 0;
        $provider = Provider::where('id', $provider_id)->first();
        if ($provider->provider_id == null) { // main provider
            $branchesIds = $provider->providers()->pluck('id')->toArray();  // branches ids
        } else {  //branch
            $branchesIds = [$provider->id];
        }

        //doctor and offers reservations
        $reservations = Reservation::where(function ($q) use ($no, $provider_id, $branchesIds) {
            $q->where(function ($qq) use ($provider_id, $branchesIds) {
                $qq->where('provider_id', $provider_id)->orWhere(function ($qqq) use ($branchesIds) {
                    $qqq->whereIN('provider_id', $branchesIds);
                });
            });
        })->where('approved', 1)
            ->whereDate('day_date', '<=', date('Y-m-d'))
            ->get();

        //services reservations
        $services_reservations = ServiceReservation::where(function ($q) use ($no, $provider_id, $branchesIds) {
            $q->where(function ($qq) use ($provider_id, $branchesIds) {
                $qq->where('branch_id', $provider_id)->orWhere(function ($qqq) use ($branchesIds) {
                    $qqq->whereIN('branch_id', $branchesIds);
                });
            });
        })->where('approved', 1)
            ->whereDate('day_date', '<=', date('Y-m-d'))
            ->get();

        if (isset($reservations) && $reservations->count() > 0) {
            foreach ($reservations as $reservation) {
                $day_date = $reservation->day_date . ' ' . $reservation->from_time;
                $reservation_date = date('Y-m-d H:i:s', strtotime($day_date));
                $currentDate = date('Y-m-d H:i:s');
                $fdate = $reservation_date;
                $tdate = $currentDate;
                $datetime1 = new DateTime($fdate);
                $datetime2 = new DateTime($tdate);
                $interval = $datetime1->diff($datetime2);
                $hours = $interval->format('%a');
                if ($hours >= 1) {
                    $need_To_finish++;
                }
            }
        }


        if (isset($services_reservations) && $services_reservations->count() > 0) {
            foreach ($services_reservations as $reservation) {
                $day_date = $reservation->day_date . ' ' . $reservation->from_time;
                $reservation_date = date('Y-m-d H:i:s', strtotime($day_date));
                $currentDate = date('Y-m-d H:i:s');
                $fdate = $reservation_date;
                $tdate = $currentDate;
                $datetime1 = new DateTime($fdate);
                $datetime2 = new DateTime($tdate);
                $interval = $datetime1->diff($datetime2);
                $hours = $interval->format('%a');
                if ($hours >= 1) {
                    $need_To_finish++;
                }
            }
        }

        return $need_To_finish;
    }

}
