<?php

namespace App\Http\Controllers\CPanel;

use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\Reason;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Models\Service;
use App\Models\ServiceReservation;
use App\Models\ServiceTime;
use App\Traits\Dashboard\ReservationTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\ReservationResource;
use Illuminate\Support\Str;
use function foo\func;

class ServicesReservationController extends Controller
{
    use GlobalTrait, SMSTrait;

    public function index(Request $request)
    {
        if ($request->reservation_id) {
            $reservation = ServiceReservation::find($request->reservation_id);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.Reservation Not Found'));
        }

        $status = 'all';
        $list = ['delay', 'all', 'today_tomorrow', 'pending', 'approved', 'reject', 'rejected_by_user', 'completed', 'complete_visited','complete_not_visited'];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $reservations = $this->getReservationByStatus();
            } else {
                $status = request('status') ? request('status') : $status;
                      $reservations = $this->getReservationByStatus($status);
            }
        }else{
            $reservations = $this->getReservationByStatus();
        }


        if ($request->type == 'clinic' or $request->type = 'home') {
            $type = $request->type == 'home' ? 1 : 2;
            $reservations = $reservations->where('service_type', $type);
        }

        if ($request->reservation_id) {
            $reservation = $reservations->find($request->reservation_id);
            $reservation->makeHidden(['paid', 'branch_id', 'provider_id', 'for_me', 'is_reported', 'reservation_total', 'mainprovider', 'rejected_reason_type', 'rejected_reason_id', 'rejection_reason', 'user_rejection_reason', 'order', 'is_visit_doctor', 'bill_total', 'latitude', 'longitude', 'admin_value_from_reservation_price_Tax']);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.No Reservations founded'));
            else
                return $this->returnData('reservations', $reservation);
        }


        $reservations = $reservations->paginate(PAGINATION_COUNT);
        $reservations->getCollection()->each(function ($reservation) {
            $reservation->makeHidden(['paid', 'branch_id', 'provider_id', 'for_me', 'is_reported', 'reservation_total', 'mainprovider', 'rejected_reason_id', 'rejection_reason', 'user_rejection_reason', 'order', 'is_visit_doctor', 'bill_total', 'latitude', 'longitude', 'admin_value_from_reservation_price_Tax']);
            return $reservation;
        });

        if (!empty($reservations) && count($reservations->toArray()) > 0) {
            $total_count = $reservations->total();
            $reservations = json_decode($reservations->toJson());
            $reservationsJson = new \stdClass();
            $reservationsJson->current_page = $reservations->current_page;
            $reservationsJson->total_pages = $reservations->last_page;
            $reservationsJson->per_page = PAGINATION_COUNT;
            $reservationsJson->total_count = $total_count;
            $reservationsJson->data = $reservations->data;
            return $this->returnData('reservations', $reservationsJson);
        }
        return $this->returnError('E001', trans('messages.No Reservations founded'));

    }


    public function destroy(Request $request)
    {
        try {
            $reservation = ServiceReservation::find($request->reservation_id);
            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('messages.No Reservations founded')], 200);

            if ($reservation->approved) {
                return response()->json(['success' => false, 'error' => __('messages.Cannot delete approved reservation')], 200);
            } else {
                $reservation->delete();
                return response()->json(['status' => true, 'msg' => __('messages.Reservation deleted successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    ##################### Start change service reservation status ########################
    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255",
                "status" => "required|in:1,2,3" // 1 == confirmed && 2 == canceled
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason = $request->rejected_reason_id;

            $reservation = ServiceReservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('messages.No reservation with this number')], 200);
            if ($reservation->approved == 1 && $request->status == 1) {
                return response()->json(['success' => false, 'error' => __('messages.Reservation already approved')], 200);
            }

            if ($reservation->approved == 2 && $request->status == 2) {
                return response()->json(['success' => false, 'error' => __('messages.Reservation already rejected')], 200);
            }

            if ($status != 2 && $status != 1 && $status != 3) {
                return response()->json(['success' => false, 'error' => __('messages.status must be 1 or 2')], 200);
            }

            if ($status == 2) {
                if ($rejection_reason == null) {
                    return response()->json(['success' => false, 'error' => __('messages.please enter rejection reason')], 200);
                }
            }

            if ($request->status == 3) {
                $validator->addRules([
                    "arrived" => "required|in:0,1"
                ]);
            }


            $arrived = 0;

            if ($request->status == 3) {

                if (!isset($request->arrived) or ($request->arrived != 0 && $request->arrived != 1)) {
                    return response()->json(['status' => false, 'error' => __('main.enter_arrived_status')], 200);
                }
                $arrived = $request->arrived;
            }

            return $this->changerReservationStatus($reservation, $request->status, $rejection_reason, $arrived, $request);


        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    ##################### End change service reservation status ########################
    public function changerReservationStatus($reservation, $status, $rejection_reason = null, $arrived = 0, $request = null)
    {
        if ($status != 3) {
            $reservation->update([
                'approved' => $status,
                'rejection_reason' => $rejection_reason
            ]);
        }


        $provider = Provider::find($reservation->branch_id); // branch
        $provider->makeVisible(['device_token']);

        $payment_method = $reservation->paymentMethod->id;   // 1- cash otherwise electronic
        $application_percentage_of_bill = $reservation->provider->application_percentage_bill ? $reservation->provider->application_percentage_bill : 0;

        $complete = $arrived;

        if ($status == 3) {  //complete Reservations
            if ($arrived == 1) {

                $reservation->update([
                    'approved' => 3,
                    'is_visit_doctor' => 1
                ]);

                if ($payment_method == 1 && $status == 3 && $complete == 1) {//1- cash reservation 3-complete reservation  1- user attend reservation
                    $totalBill = 0;
                    $comment = " نسبة ميدكال كول من كشف (خدمة) حجز نقدي ";
                    $invoice_type = 0;
                    try {
                        $this->calculateServiceReservationBalanceForAdmin($application_percentage_of_bill, $reservation);
                    } catch (\Exception $ex) {
                    }
                }

                /*   if ($payment_method != 1 && $status == 3 && $complete == 1) {//  visa reservation 3-complete reservation  1- user attend reservation
                       $totalBill = 0;
                       $comment = " نسبة ميدكال كول من كشف (خدمة) حجز الكتروني ";
                       $invoice_type = 0;
                       try {
                           $this->calculateOfferReservationBalanceForAdmin($application_percentage_of_bill, $reservation);
                       } catch (\Exception $ex) {
                       }
                   }*/

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

                if ($status == 1 or $status == 2) {
                    //send mobile sms
                    $message = $bodyUser;
                    $this->sendSMS($reservation->user->mobile, $message);
                }
            }
        } catch (\Exception $exception) {


        }
        return response()->json(['status' => true, 'msg' => __('main.reservation_status_changed_successfully')]);
    }


    protected function calculateServiceReservationBalanceForAdmin($application_percentage_of_bill, ServiceReservation $reservation)
    {

        // $reservation->service_type == 1; ### 1 = home & 2 = clinic
        $total_amount = floatval($reservation->price);
        $MC_percentage = $application_percentage_of_bill;
        $reservationBalanceBeforeAdditionalTax = ($total_amount * $MC_percentage) / 100;
        $additional_tax_value = ($reservationBalanceBeforeAdditionalTax * 5) / 100;

        if ($reservation->paymentMethod->id == 1) {//cash
            $discountType = " فاتورة حجز نقدي لخدمة ";
            $reservationBalance = ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);

            $branch = $reservation->branch;  // always get branch
            $branch->update([
                'balance' => $branch->balance - $reservationBalance,
            ]);
            $reservation->update([
                'discount_type' => $discountType,
            ]);
            /*$manager = $this->getAppInfo();
            $manager->update([
                'balance' => $manager->unpaid_balance + $reservationBalance
            ]);*/
        } else {

            $discountType = " فاتورة حجز الكتروني لخدمة ";
            $reservationBalance = $total_amount - ($reservationBalanceBeforeAdditionalTax + $additional_tax_value);

            $branch = $reservation->branch;  // always get branch
            $branch->update([
                'balance' => $branch->balance + $reservationBalance,
            ]);
            $reservation->update([
                'discount_type' => $discountType,
            ]);
            /*$manager = $this->getAppInfo();
            $manager->update([
                'balance' => $manager->unpaid_balance + $reservationBalance
            ]);*/

        }

        return true;
    }


    public function edit(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "id" => "required|exists:service_reservations,id",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation = ServiceReservation::with('service')->select('id', 'reservation_no', 'day_date', 'from_time',
                'to_time', 'service_id', 'service_type', 'branch_id', 'provider_id')
                ->find($request->id);
            if (!$reservation) {
                return $this->returnError('E001', __('main.not_found'));
            }

            $days = ServiceTime::where('service_id', $reservation->service_id)
                ->get();

            if ($reservation->approved == 2 or $reservation->approved == 3) {   // 2-> cancelled  3 -> complete
                return $this->returnError('E001', __('main.appointment_for_this_reservation_cannot_be_updated'));
            }

            $reservation->days = $days;

            $reservation->makeVisible(['service_id']);
            $reservation->makeHidden(["for_me",
                "branch_name",
                "branch_no",
                "is_reported",
                "mainprovider",
                "admin_value_from_reservation_price_Tax",
                "reservation_total",
                "rejected_reason_type",
                "comment_report"]);
            return $this->returnData('reservation', $reservation);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getClinicServiceAvailableTimes(Request $request)
    {
        try {

            $rules = [
                "date" => "required|date_format:Y-m-d",
                "service_id" => "required|exists:services,id",
                "service_type" => "required|in:1,2",
                "reserve_duration" => "nullable|required_if:service_type,1"
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $requestData = $request->all();
            $dayName = Str::lower(date('D', strtotime($requestData['date'])));

            $serviceTimes = [];


            if ($requestData['service_type'] == 2) { // clinic
                $service = Service::whereHas('types', function ($q) {
                    $q->where('type_id', 2);
                })->find($requestData['service_id']);
                if ($service) {
//                     $serviceTimes = $service->times()->where('day_code', $dayName)->get();
                    $times = $service->times()->where('day_code', $dayName)->get();
                    foreach ($times as $key => $value) {
                        $splitTimes = $this->splitTimes($value->from_time, $value->to_time, $service->reservation_period);
                        foreach ($splitTimes as $k => $v) {
                            $s = [];
                            $s['id'] = $value->id;
                            $s['day_name'] = $value->day_name;
                            $s['day_code'] = $value->day_code;
                            $s['from_time'] = $v['from'];
                            $s['to_time'] = $v['to'];
                            $s['branch_id'] = $value->branch_id;

                            array_push($serviceTimes, $s);
                        }

                    }
                }
            } else {
                $service = Service::whereHas('types', function ($q) {
                    $q->where('type_id', 1);
                })->find($requestData['service_id']);
                if ($service) {
                    $times = $service->times()->where('day_code', $dayName)->get();
                    foreach ($times as $key => $value) {
                        $splitTimes = $this->splitTimes($value->from_time, $value->to_time, $requestData['reserve_duration']);
                        foreach ($splitTimes as $k => $v) {
                            $s = [];
                            $s['id'] = $value->id;
                            $s['day_name'] = $value->day_name;
                            $s['day_code'] = $value->day_code;
                            $s['from_time'] = $v['from'];
                            $s['to_time'] = $v['to'];
                            $s['branch_id'] = $value->branch_id;

                            array_push($serviceTimes, $s);
                        }

                    }
                }
            }

            if ($serviceTimes) {

                ########### Start To Get Times After The Current Time ############
                $collection = collect($serviceTimes);
                $dayDate = $requestData['date'];

                $filtered = $collection->filter(function ($value, $key) use ($dayDate) {

                    // Check if this time is reserved before or not
                    $checkTime = ServiceReservation::where('day_date', $dayDate)
                        ->where('from_time', $value['from_time'])
                        ->where('to_time', $value['to_time'])
                        ->first();

                    if (date('Y-m-d') == $dayDate)
                        return strtotime($value['from_time']) > strtotime(date('H:i:s')) && $checkTime == null;
                    else
                        return $checkTime == null;

                });
                $serTimes = array_values($filtered->all());
                ########### End To Get Times After The Current Time ############

                return $this->returnData('times', $serTimes);
            }
            return $this->returnError('E001', __('main.appointment_for_this_reservation_cannot_be_updated'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function splitTimes($StartTime, $EndTime, $Duration = "30")
    {
        $returnArray = [];// Define output
        $StartTime = strtotime($StartTime); //Get Timestamp
        $EndTime = strtotime($EndTime); //Get Timestamp

        $addMinutes = $Duration * 60;

        for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {
                $returnArray[$i]['from'] = $from;
                $returnArray[$i]['to'] = $to;
            }
        }
        return $returnArray;
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_no" => "required|max:255",
                "day_date" => "required|date",
                "from_time" => "required",
                "to_time" => "required",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            DB::beginTransaction();
            $reservation = ServiceReservation::where('reservation_no', $request->reservation_no)->with('user')->first();
            if ($reservation == null) {
                return $this->returnError('E001', __('main.there_is_no_reservation_with_this_number'));
            }
            $provider = Provider::find($reservation->branch_id);

            $service = Service::find($reservation->service_id);
            if ($service == null) {
                return $this->returnError('E001', __('messages.No service with this id'));
            }


            $reservation->update([
                "day_date" => date('Y-m-d', strtotime($request->day_date)),
                "from_time" => date('H:i:s', strtotime($request->from_time)),
                "to_time" => date('H:i:s', strtotime($request->to_time)),
                'order' => 0,
                //"approved" => 1,
            ]);

            DB::commit();
            try {
//                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated user reservation')]))->sendProvider($reservation->provider);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated user reservation')]))->sendProvider($reservation->branch);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated your reservation')]))->sendUser($reservation->user);
            } catch (\Exception $ex) {

            }

            return $this->returnSuccessMessage(__('messages.Reservation updated successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }

    private function getReservationByStatus($status = 'all')
    {

        if ($status == 'delay') {
            $allowTime = 15;  // 15 min
            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price',)
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime);

        } elseif ($status == 'pending') {
            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])
                ->where('approved', 0);

        } elseif ($status == 'approved') {
            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])
                ->where('approved', 1);
        } elseif ($status == 'reject') {

            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])
                ->where('approved', 2)
                ->whereNotNull('rejection_reason')
                ->where('rejection_reason', '!=', '');

        } elseif ($status == 'rejected_by_user') {
            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])
                ->where('approved', 2)
                ->whereNotNull('rejected_reason_notes');
        } elseif ($status == 'complete_visited') {
            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])
                ->where('approved', 3)
                ->where('is_visit_doctor', 1);
              } elseif ($status == 'complete_not_visited') {

             return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ])
                ->where('approved', 2)
                ->
                where(function ($q) {
                    $q->whereNull('rejected_reason_notes')
                        ->orwhere('rejected_reason_notes', '=', '')
                        ->orwhere('rejected_reason_notes', 0);
                })
                -> where(function ($q) {
                    $q->whereNull('rejection_reason')
                        ->orwhere('rejection_reason', '=', '')
                        ->orwhere('rejection_reason', 0);
                });

        } else {
            return ServiceReservation::with(['service' => function ($g) {
                $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'),'price','clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'insurance_image', 'insurance_company_id')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'branch' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
            },
                'rejectionResoan' => function ($q) {
                    $q->select('id', 'name_' . app()->getLocale() . ' as name');
                }
            ]);
        }
    }


}
