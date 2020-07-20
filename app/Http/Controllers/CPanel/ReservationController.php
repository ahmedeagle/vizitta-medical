<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\ProviderResource;
use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\Reason;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Models\ServiceReservation;
use App\Traits\Dashboard\ReservationTrait;
use App\Traits\CPanel\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\ReservationResource;

class ReservationController extends Controller
{
    use ReservationTrait, GeneralTrait;

    public function index()
    {
        $data = [];
        $data['reasons'] = Reason::get();
        $status = 'all';
        $list = ['delay', 'all', 'today_tomorrow', 'pending', 'approved', 'reject', 'rejected_by_user', 'completed', 'complete_visited', 'complete_not_visited'];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $data['reservations'] = new ReservationResource($this->getReservationByStatus());
            } else {
                $status = request('status') ? request('status') : $status;
                $data['reservations'] = new ReservationResource($this->getReservationByStatus($status));

            }
            return response()->json(['status' => true, 'data' => $data]);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $res = Reservation::whereNotNull('doctor_id')
                ->where('doctor_id', '!=', 0)
                ->where(function ($query) use ($q) {
                    $query->where('reservation_no', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('day_date', 'LIKE binary', '%' . trim($q) . '%')
                        ->orWhere('from_time', 'LIKE binary', '%' . trim($q) . '%')
                        ->orWhere('to_time', 'LIKE binary', '%' . trim($q) . '%')
                        ->orWhere('price', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('bill_total', 'LIKE', '%' . trim($q) . '%')
                        ->orWhere('discount_type', 'LIKE', '%' . trim($q) . '%')
                        ->orWhereHas('user', function ($query) use ($q) {
                            $query->where('name', 'LIKE', '%' . trim($q) . '%');
                        })
                        ->orWhereHas('doctor', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                        })->orWhereHas('paymentMethod', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                        })
                        ->orWhereHas('branch', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                            $query->orWhereHas('provider', function ($query) use ($q) {
                                $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                            });
                        })
                        ->orWhere(function ($qq) use ($q) {
                            if (trim($q) == 'معلق') {
                                $qq->where('approved', 0);
                            } elseif (trim($q) == 'مقبول') {
                                $qq->where('approved', 1);
                            } elseif (trim($q) == 'مرفوض') {
                                $qq->whereIn('approved', [2, 5]);
                            } elseif (trim($q) == 'مكتمل') {
                                $qq->where('approved', 3);
                            }
                        });
                })
                ->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);

            $data['reservations'] = new ReservationResource($res);

        } else {
            $data['reservations'] = new ReservationResource(Reservation::orderBy('day_date', 'DESC')
                ->paginate(10));
        }
        return response()->json(['status' => true, 'data' => $data]);
    }


    protected function getReservationByStatus($status = 'all')
    {
        if ($status == 'delay') {
            $allowTime = 15;  // 15 min
            return $reservaitons = Reservation::selection()
                ->where('approved', 0)->
                whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)
                ->orderBy('day_date', 'DESC')
                ->paginate(10);
        } elseif ($status == 'today_tomorrow') {
            return $reservaitons = Reservation::selection()->where('approved', '!=', 2)->where(function ($q) {
                $q->whereDate('day_date', Carbon::today())->orWhereDate('day_date', Carbon::tomorrow());
            })->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'pending') {
            return $reservaitons = Reservation::selection()->where('approved', 0)->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'approved') {
            return $reservaitons = Reservation::selection()->where('approved', 1)->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'reject') {
            return $reservaitons = Reservation::selection()->where('approved', 2)->whereNotNull('rejection_reason')->where('rejection_reason', '!=', '')->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'rejected_by_user') {
            $reservaitons = Reservation::selection()->where('approved', 5)->paginate(10);
            $reservaitons->count = Reservation::selection()->where('approved', 5)->count();
            return $reservaitons;
        } elseif ($status == 'completed') {
            return $reservaitons = Reservation::selection()->where('approved', 3)->orderBy('day_date', 'DESC')->orderBy('from_time', 'ASC')->paginate(10);
        } elseif ($status == 'complete_visited') {
            return $reservaitons = Reservation::selection()->where('approved', 3)->orderBy('day_date', 'DESC')->paginate(10);
        } elseif ($status == 'complete_not_visited') {
            return $reservaitons = Reservation::selection()->where('approved', 2)->where(function ($q) {
                $q->whereNull('rejection_reason')->orwhere('rejection_reason', '=', '')->orwhere('rejection_reason', 0);
            })->orderBy('day_date', 'DESC')->paginate(10);
        } else {
            return $reservaitons = Reservation::selection()->orderBy('day_date', 'DESC')->paginate(10);
        }
    }

    public function providerReservations($provider_id, Request $request)
    {
        $data = [];
        $data['provider'] = Provider::findOrFail($provider_id);
        $data['branchsIds'] = $data['provider']->providers()->pluck('id', 'name_ar')->toArray();
        $data['count'] = Reservation::whereIn('provider_id', $data['branchsIds'])->whereIn('approved', [3])->count();   // only completed reservations

        $totalReservationPrice = 0;

        $completeReservations = Reservation::whereIn('provider_id', $data['branchsIds'])->whereIn('approved', [3])->get();

        if (isset($completeReservations) && $completeReservations->count() > 0) {   //some only complete price
            foreach ($completeReservations as $res) {
                $totalReservationPrice += $res->reservation_total;
            }
        }
        $data['total'] = $totalReservationPrice;
        if (isset($completeReservations) && $completeReservations->count() > 0) {   //some only complete price
            foreach ($completeReservations as $res) {
                $totalReservationPrice += $res->reservation_total;
            }
        }


        $data['doctorsIds'] = Doctor::whereIn('provider_id', $data['branchsIds'])->pluck('id', 'name_ar')->toArray();
        $data['paymentMethodIds'] = PaymentMethod::pluck('id', 'name_ar')->toArray();
        //falter keys

        $data['from_date'] = $request->filled('from_date') ? $request->from_date : '';
        $data['to_date'] = $request->filled('to_date') ? $request->to_date : '';
        $data['doctor_id'] = $request->filled('doctor_id') ? $request->doctor_id : '';
        $data['branch_id'] = $request->filled('branch_id') ? $request->branch_id : '';
        $data['payment_method_id'] = $request->filled('payment_method_id') ? $request->payment_method_id : '';

        return view('provider.reservations.index', $data);
    }

    public function getProviderReservationsDataTable($provider_id, Request $request)
    {
        return $this->getReservationByProviderId($provider_id, $request);
    }

    public function show(Request $request)
    {
        try {
            $reservation = $this->getReservationDetailsById($request->id);
            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $reservation->makeHidden(['rejection_reason']);

            return response()->json(['status' => true, 'data' => $reservation]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function changeStatus(Request $request)
    {
        $reservation = Reservation::where('id', $request->id)->with('user')->first();

        if ($reservation == null)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

        if ($reservation->approved == 1 && $request->status == 1) {
            return response()->json(['status' => false, 'error' => __('messages.Reservation already approved')], 200);
        }

        if ($reservation->approved == 2 && $request->status == 1) {
            return response()->json(['status' => false, 'error' => __('messages.Reservation already rejected')], 200);
        }

        if ($request->status != 2 && $request->status != 1 && $request->status != 3) {
            return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
        }

        if ($request->status == 2) {
            if ($request->rejection_reason == null && $request->rejection_reason != 0) {
                return response()->json(['status' => false, 'error' => __('main.enter_reservation_rejected_reason')], 200);
            }
        }

        $arrived = 0;

        if ($request->status == 3) {

            if (!isset($request->arrived) or ($request->arrived != 0 && $request->arrived != 1)) {
                return response()->json(['status' => false, 'error' => __('main.enter_arrived_status')], 200);
            }
            $arrived = $request->arrived;
        } else {

            $reservation->update([
                'approved' => $request->status,
            ]);
        }

        return $this->changerReservationStatus($reservation, $request->status, $request->rejection_reason, $arrived, $request);

    }

    public function rejectReservation(Request $request)
    {
        $id = $request->id;
        $status = $request->status;
        $rejection_reason = $request->rejection_reason;

        $reservation = Reservation::where('id', $id)->with('user')->first();
        if ($reservation == null)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

        if ($reservation->approved == 2) {
            return response()->json(['status' => false, 'error' => __('messages.Reservation already rejected')], 200);
        }

        if ($status != 2) {
            return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
        } else {

            if ($status == 2) {
                if ($rejection_reason == null or !is_numeric($rejection_reason)) {
                    return response()->json(['status' => false, 'error' => __('main.enter_reservation_rejected_reason')], 200);
                }
            }
            $this->changerReservationStatus($reservation, $status, $rejection_reason, 0, $request);
            return response()->json(['status' => true, 'msg' => __('main.reservation_status_changed_successfully')]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $reservation = $this->getReservationById($request->id);
            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($reservation->approved) {
                return response()->json(['status' => false, 'error' => __('main.accepted_reservation_cannot_be_deleted')], 200);
            } else {
                $reservation->delete();
                return response()->json(['status' => true, 'msg' => __('main.reservation_deleted_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $reservation = Reservation::find($request->id);
            if (!$reservation) {
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            }
            if ($reservation->approved == 2 or $reservation->approved == 3) {   // 2-> cancelled  3 -> complete
                return response()->json(['status' => false, 'error' => __('main.appointment_for_this_reservation_cannot_be_updated')], 200);
            }
            $doctor_id = $reservation->doctor->id;
            if (!$doctor_id || $doctor_id == 0) {
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            }
            $result['reservation'] = $reservation;
            $result['doctor_id_for_edit_reservation'] = $doctor_id;
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
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
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            DB::beginTransaction();
            $reservation = Reservation::where('reservation_no', $request->reservation_no)->with('user')->first();
            if ($reservation == null) {
                return response()->json(['status' => false, 'error' => __('main.there_is_no_reservation_with_this_number')], 200);
            }
            $provider = Provider::find($reservation->provider_id);

            $doctor = $reservation->doctor;
            if ($doctor == null) {
                return response()->json(['status' => false, 'error' => __('messages.No doctor with this id')], 200);
            }

            $hasReservation = $this->checkReservationInDate($doctor->id, $request->day_date, $request->from_time, $request->to_time);
            if ($hasReservation) {
                return response()->json(['status' => false, 'error' => __('messages.This time is not available')], 200);
            }

            $reservationDayName = date('l', strtotime($request->day_date));
            $rightDay = false;
            $timeOrder = 1;
            $last = false;
            $times = $this->getDoctorTimesInDay($doctor->id, $reservationDayName);
            foreach ($times as $key => $time) {
                if ($time['from_time'] == Carbon::parse($request->from_time)->format('H:i')
                    && $time['to_time'] == Carbon::parse($request->to_time)->format('H:i')) {
                    $rightDay = true;
                    $timeOrder = $key + 1;
                    //if(count($times) == ($key+1))
                    //  $last = true;
                    break;
                }
            }
            if (!$rightDay) {
                return response()->json(['status' => false, 'error' => __('messages.This day is not in doctor days')], 200);
            }

            $reservation->update([
                "day_date" => date('Y-m-d', strtotime($request->day_date)),
                "from_time" => date('H:i:s', strtotime($request->from_time)),
                "to_time" => date('H:i:s', strtotime($request->to_time)),
                'order' => $timeOrder,
                //"approved" => 1,
            ]);

            if ($last) {
                ReservedTime::create([
                    'doctor_id' => $doctor->id,
                    'day_date' => date('Y-m-d', strtotime($request->day_date))
                ]);
            }

            if ($reservation->user->email != null)
                Mail::to($reservation->user->email)->send(new AcceptReservationMail($reservation->reservation_no));

            DB::commit();
            try {
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated user reservation')]))->sendProvider($reservation->provider);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated your reservation')]))->sendUser($reservation->user);
            } catch (\Exception $ex) {

            }
            return response()->json(['status' => true, 'msg' => __('messages.Reservation updated successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    protected function checkReservationInDate($doctorId, $dayDate, $fromTime, $toTime)
    {
        // effect by date
        $reservation = Reservation::where([
            ['doctor_id', '=', $doctorId],
            ['day_date', '=', Carbon::parse($dayDate)->format('Y-m-d')],
            ['from_time', '=', $fromTime],
            ['to_time', '=', $toTime],
        ])->where('approved', '!=', 2)->first();
        if ($reservation != null)
            return true;

        else
            return false;
    }

    protected function getDoctorTimesInDay($doctorId, $dayName, $count = false)
    {
        // effect by date
        $doctorTimes = DoctorTime::query();
        $doctorTimes = $doctorTimes->where('doctor_id', $doctorId)->whereRaw('LOWER(day_name) = ?', strtolower($dayName))
            ->orderBy('created_at')->orderBy('order');

        $times = $this->getDoctorTimePeriods($doctorTimes->get());
        if ($count)
            if (!empty($times) && is_array($times))
                return count($times);
            else
                return 0;

        return $times;
    }

    protected function getDoctorTimePeriods($working_days)
    {
        $times = [];
        $j = 0;
        foreach ($working_days as $working_day) {
            $from = strtotime($working_day['from_time']);
            $to = strtotime($working_day['to_time']);
            $diffInterval = ($to - $from) / 60;
            $periodCount = $diffInterval / $working_day['time_duration'];
            for ($i = 0; $i < round($periodCount); $i++) {
                $times[$j]['day_code'] = $working_day['day_code'];
                $times[$j]['day_name'] = $working_day['day_name'];
                $times[$j]['from_time'] = Carbon::parse($working_day['from_time'])->addMinutes($working_day['time_duration'] * $i)->format('H:i');
                $times[$j]['to_time'] = Carbon::parse($working_day['from_time'])->addMinutes($working_day['time_duration'] * ($i + 1))->format('H:i');
                $times[$j++]['time_duration'] = $working_day['time_duration'];
            }
        }
        return $times;
    }


    public function getApprovedReservations(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "from_date" => "sometimes|nullable|date_format:Y-m-d",
                "to_date" => "sometimes|nullable|date_format:Y-m-d",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $conditions = [];
            if ($request->has('from_date')) {
                array_push($conditions, ['day_date', '>=', $request->from_date]);
            }

            if ($request->has('to_date')) {
                array_push($conditions, ['day_date', '<=', $request->from_date]);
            }

            if (!empty($conditions)) {
                $doctor_reservations = Reservation::query()->where($conditions);
                $home_services_reservations = ServiceReservation::query()->where($conditions);
                $clinic_services_reservations = ServiceReservation::query()->where($conditions);
                $reservations = Reservation::query()->where($conditions);
            } else {
                $doctor_reservations = Reservation::query();
                $home_services_reservations = ServiceReservation::query();
                $clinic_services_reservations = ServiceReservation::query();
                $reservations = Reservation::query();
            }

            $doctor_reservations = $doctor_reservations->doctorSelection()
                ->where('approved', 1)
                ->whereNotNull('doctor_id')
                ->where('doctor_id', '!=', 0)
                ->orderBy('id', 'DESC');


            $home_services_reservations = $home_services_reservations->serviceSelection()
                ->serviceSelection()
                ->whereHas('type', function ($e) {
                    $e->where('id', 1);
                })
                ->where('approved', 1)
                ->orderBy('id', 'DESC');

            $clinic_services_reservations = $clinic_services_reservations->serviceSelection()->whereHas('type', function ($e) {
                $e->where('id', 2);
            })
                ->where('approved', 1)
                ->orderBy('id', 'DESC');

            $reservations = $reservations -> OfferReservationSelection()->with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at',
                    'price',
                    'price_after_discount'
                );
            }, 'doctor' => function ($g) {
                $g->select('id', 'nickname_id', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                    ->with(['nickname' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }, 'specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'rejectionResoan' => function ($rs) {
                $rs->select('id', DB::raw('name_' . app()->getLocale() . ' as rejection_reason'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'user' => function ($q) {
                $q->select('id', 'name', 'mobile', 'email', 'address', 'insurance_image', 'insurance_company_id', 'mobile')
                    ->with(['insuranceCompany' => function ($qu) {
                        $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'people' => function ($p) {
                $p->select('id', 'name', 'insurance_company_id', 'insurance_image')->with(['insuranceCompany' => function ($qu) {
                    $qu->select('id', 'image', DB::raw('name_' . app()->getLocale() . ' as name'));
                }]);
            }, 'provider' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'service' => function ($g) {
                $g->select('id', 'specification_id', \Illuminate\Support\Facades\DB::raw('title_' . app()->getLocale() . ' as title'), 'price', 'clinic_price', 'home_price')
                    ->with(['specification' => function ($g) {
                        $g->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'type' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 1)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                /*  ->whereDate('day_date', '>=', Carbon::now()->format('Y-m-d'))*/
                ->orderBy('id', 'DESC')
                ->union($doctor_reservations)
                ->union($home_services_reservations)
                ->union($clinic_services_reservations)
                ->paginate(PAGINATION_COUNT);

            if (count($reservations->toArray()) > 0) {
                $reservations->getCollection()->each(function ($reservation) use ($request) {
                    $reservation->makeHidden(['order', 'rejected_reason_type', 'reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_notes', 'rejected_reason_id', 'is_visit_doctor', 'rejection_reason', 'user_rejection_reason']);

                    $this->addReservationTypeToResult($reservation);
                    $reservation->makeHidden(["offer_id", "doctor_id", "service_id", "doctor_rate",
                        "service_rate",
                        "provider_rate",
                        "offer_rate",
                        "paid", "use_insurance",
                        "promocode_id",
                        "provider_id",
                        "branch_id", "rate_comment",
                        "rate_date",
                        "address", "latitude",
                        "longitude"]);

                    $reservation->doctor->makeHidden(['available_time', 'times']);
                    $reservation->provider->makeHidden(["provider_has_bill",
                        "has_insurance",
                        "is_lottery",
                        "rate_count"]);

                    return $reservation;
                });

                  $total_count = $reservations->total();
                $reservations = json_decode($reservations->toJson());
                $reservationsJson = new \stdClass();
                $reservationsJson->current_page = $reservations->current_page;
                $reservationsJson->total_pages = $reservations->last_page;
                $reservationsJson->total_count = $total_count;
                $reservationsJson->per_page = PAGINATION_COUNT;
                $reservationsJson->data = $reservations->data;
                return $this->returnData('reservations', $reservationsJson);
            }
            return $this->returnError('E001','لايوجد نتائح حتي اللحظه');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    private function addReservationTypeToResult($reservation)
    {
        if ($reservation->doctor_id != null && $reservation->doctor_id != 0 && $reservation->doctor_id != "") {
            $reservation->reservation_type = "doctor";
        } elseif ($reservation->offer_id != null && $reservation->offer_id != 0 && $reservation->offer_id != "") {
            $reservation->reservation_type = "offer";
        } elseif (isset($reservation->type->id) && $reservation->type->id = 1) {
            $reservation->reservation_type = "home_services";
        } elseif (isset($reservation->type->id) && $reservation->type->id = 2) {
            $reservation->reservation_type = "clinic_services";
        } else {
            $reservation->reservation_type = "undefined";
        }
    }



}
