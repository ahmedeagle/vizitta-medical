<?php

namespace App\Http\Controllers\CPanel;

use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\Offer;
use App\Models\OfferBranchTime;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\Reason;
use App\Models\Reservation;
use App\Models\ReservedTime;
use App\Traits\Dashboard\GlobalOfferTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\ReservationResource;
use DateTime;
class offersReservationController extends Controller
{
    use GlobalOfferTrait, GlobalTrait;

    public function index()
    {
        $data = [];
        $data['reasons'] = Reason::get();
        $status = 'all';
        $list = ['delay', 'all', 'today_tomorrow', 'pending', 'approved', 'reject', 'rejected_by_user', 'completed', 'complete_visited', 'complete_not_visited'];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $data['reservations'] = $this->getReservationByStatus();
            } else {
                $status = request('status') ? request('status') : $status;
                $data['reservations'] = $this->getReservationByStatus($status);
            }

        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $data['reservations'] = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
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
                        ->orWhereHas('offer', function ($query) use ($q) {
                            $query->where('title_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('title_en', 'LIKE', '%' . trim($q) . '%');
                        })->orWhereHas('paymentMethod', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
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
                        })
                        ->orWhereHas('provider', function ($query) use ($q) {
                            $query->where(function ($query) use ($q) {
                                $query->where('name_en', 'LIKE', '%' . trim($q) . '%')
                                    ->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                            })
                                ->orWhereHas('provider', function ($query) use ($q) {
                                    $query->where('name_en', 'LIKE', '%' . trim($q) . '%')
                                        ->orwhere('name_ar', 'LIKE', '%' . trim($q) . '%');
                                });

                        });


                })->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);

        } else {
            $data['reservations'] = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        }


        ##################### paginate data #######################
        $total_count = $data['reservations']->total();
        $offer_reservations = json_decode($data['reservations']->toJson());
        $offerJson = new \stdClass();
        $offerJson->current_page = $offer_reservations->current_page;
        $offerJson->total_pages = $offer_reservations->last_page;
        $offerJson->total_count = $total_count;
        $offerJson->per_page = PAGINATION_COUNT;
        $offerJson->data = $offer_reservations->data;

        ###################### end paginate data ##################
        return $this->returnData('offer_reservations', $offerJson);
    }


    public function edit(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "id" => "required|exists:reservations,id",
             ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation = Reservation::select('id','reservation_no','day_date','from_time','to_time','offer_id', 'provider_id')->find($request->id);
            if (!$reservation) {
                return $this->returnError('E001', __('main.not_found'));
            }

            $days = OfferBranchTime::where('offer_id', $reservation->offer_id)
                ->where('branch_id', $reservation->provider_id)
                ->get();

            if ($reservation->approved == 2 or $reservation->approved == 3) {   // 2-> cancelled  3 -> complete
                return $this->returnError('E001', __('main.appointment_for_this_reservation_cannot_be_updated'));
            }

            $reservation -> days = $days;

            return $this->returnData('reservation', $reservation);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
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
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            DB::beginTransaction();
            $reservation = Reservation::where('reservation_no', $request->reservation_no)->with('user')->first();
            if ($reservation == null) {
                 return $this->returnError('E001',__('main.there_is_no_reservation_with_this_number'));
            }
            $provider = Provider::find($reservation->provider_id);

            $offer = $reservation->offer;
            if ($offer == null) {
                 return $this->returnError('E001',__('messages.No offer with this id'));
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
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated user reservation')]))->sendProvider($reservation->provider);
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.Reservation Status'), 'body' => __('messages.The branch') . $provider->getTranslatedName() . __('messages.updated your reservation')]))->sendUser($reservation->user);
            } catch (\Exception $ex) {

            }

            return $this->returnSuccessMessage( __('messages.Reservation updated successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

//get availbles  slot times by day
    public function getAvailableTimes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "offer_id" => "required|exists:offers,id",
                "branch_id" => "required|exists:providers,id",
                "date" => "required|date_format:Y-m-d",
            ]);


            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $date = $request->date;
            $offer = Offer::find($request->offer_id);
            $branch = Provider::whereNotNull('provider_id')->find($request->branch_id);
            if (!$branch)
                return $this->returnError('E001', trans('messages.No doctor with this id'));

            $d = new DateTime($date);
            $day_name = strtolower($d->format('l'));
            $days_name = ['saturday' => 'sat', 'sunday' => 'sun', 'monday' => 'mon', 'tuesday' => 'tue', 'wednesday' => 'wed', 'thursday' => 'thu', 'friday' => 'fri'];
            $dayCode = $days_name[$day_name];


            if ($offer != null) {
                $day = $offer->times()->where('branch_id', $branch->id)->where('day_code', $dayCode)->first();
                $doctorTimesCount = $this->getOfferTimePeriodsInDay($day, $dayCode, true);
                $times = [];
                $date = $request->date;
                $offerTimesCount = $this->getOfferTimePeriodsInDay($day, $dayCode, true);
                $availableTime = $this->getAllOfferAvailableTime($offer->id, $request->branch_id, $offerTimesCount, [$day], $date);
                if (count((array)$availableTime))
                    array_push($times, $availableTime);

                $res = [];
                if (count($times)) {
                    foreach ($times as $key => $time) {
                        $res = array_merge_recursive($time, $res);
                    }
                }
                $offer->times = $res;

                ########### Start To Get Doctor Times After The Current Time ############
                $collection = collect($offer->times);
                $filtered = $collection->filter(function ($value, $key) {

                    if (date('Y-m-d') == $value['date'])
                        return strtotime($value['from_time']) > strtotime(date('H:i:s'));
                    else
                        return $value;
                });
                $offer->times = array_values($filtered->all());
                ########### End To Get Doctor Times After The Current Time ############

                return $this->returnData('offer_available_times', $offer->times);
            }

            return $this->returnError('E001', trans('messages.No Offer with this id'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    protected function getReservationByStatus($status = 'all')
    {
        if ($status == 'delay') {
            $allowTime = 15;  // 15 min
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->where('approved', 0)
                ->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'today_tomorrow') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', '!=', 2)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->where(function ($q) {
                    $q->whereDate('day_date', Carbon::today())
                        ->orWhereDate('day_date', Carbon::tomorrow());
                })->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'pending') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 0)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'approved') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 1)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'reject') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 2)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->whereNotNull('rejection_reason')
                ->where('rejection_reason', '!=', '')
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'rejected_by_user') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 5)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'completed') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->where('approved', 3)
                ->orderBy('day_date', 'DESC')
                ->orderBy('from_time', 'ASC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'complete_visited') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 3)
                ->orderBy('day_date', 'DESC')
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } elseif ($status == 'complete_not_visited') {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->where('approved', 2)
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->where(function ($q) {
                    $q->whereNull('rejection_reason')
                        ->orwhere('rejection_reason', '=', '')
                        ->orwhere('rejection_reason', 0);
                })
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        } else {
            return $reservaitons = Reservation::with(['offer' => function ($q) {
                $q->select('id',
                    DB::raw('title_' . app()->getLocale() . ' as title'),
                    'expired_at'
                );
            }, 'provider' => function ($que) {
                $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
                ->whereNotNull('offer_id')
                ->where('offer_id', '!=', 0)
                ->orderBy('day_date', 'DESC')
                ->offerSelection()
                ->paginate(PAGINATION_COUNT);
        }
    }

    public function show(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $reservation = $this->getReservationByNoWihRelation2($request->reservation_id);
            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No reservation with this number'));

            return $this->returnData('reservation', $reservation);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation = $this->getReservationById($request->reservation_id);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.No reservation with this number'));

            if ($reservation->approved != 0) {
                return $this->returnError('E001', trans('messages.reservation cannot delete'));
            }
            $reservation->delete();
            return $this->returnSuccessMessage(trans('messages.reservation deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function changeStatus(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255",
                "status" => "required|in:1,2,3" // 1 approved 2 reject
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason = $request->reason;

            $reservation = Reservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return $this->returnError('E001', trans('messages.No reservation with this number'));
            if ($reservation->approved == 1 && $request->status == 1) {
                return $this->returnError('E001', trans('messages.Reservation already approved'));
            }

            if ($reservation->approved == 2 && $request->status == 2) {
                return $this->returnError('E001', trans('messages.Reservation already rejected'));
            }

            if ($status != 2 && $status != 1 && $request->status != 3) {
                return $this->returnError('E001', trans('messages.status must be 1 or 2'));
            }

            if ($status == 2) {
                if ($rejection_reason == null) {
                    return $this->returnError('E001', trans('messages.please enter rejection reason'));
                }
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
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    protected  function getOfferTimePeriodsInDay($working_day, $day_code, $count = false)
    {
        $times = [];
        $j = 0;
        if ($working_day['day_code'] == $day_code) {
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
        if ($count)
            return count($times);
        return $times;
    }

    protected function getAllOfferAvailableTime($offerId,$branchId, $timeCountInDay, $days, $timeDate, $count = 0)
    {
        // effect by date
        $getAllAvailableTime = [];
        if ($count > 60)
            return new \stdClass();
        $dayName = $this->getDayByCode($days[$count % count($days)]['day_code']);
        $reservationsCount = $this->getOfferAvailableReservationInDate($offerId,$branchId, $timeDate, true);
        $offerTimes = $this->getOfferTimesInDay($offerId,$branchId, $dayName);
        foreach ($offerTimes as $key => $dTime) {
            $reservation = $this->getOfferReservationInTime($offerId,$branchId, $timeDate, $dTime['from_time'], $dTime['to_time']);
            if ($reservation != null)
                continue;
            else

                $avTime = ['date' => $timeDate, 'day_name' => trans('messages.' . $dayName),
                    'day_code' => trans('messages.' . $dayName . ' Code'), 'from_time' => $dTime['from_time'], 'to_time' => $dTime['to_time']];
            array_push($getAllAvailableTime, $avTime);
        }
        return $getAllAvailableTime;

    }

    protected function getOfferAvailableReservationInDate($offerId,$branchId, $dayDate, $count = false)
    {
        $reservation = Reservation::query();
        if ($dayDate instanceof Carbon)
            return $dayDate = date($dayDate->format('Y-m-d'));
        $query = "(SELECT COUNT(*)  FROM reservations WHERE day_date = '" . $dayDate . "' and offer_id = '" . $offerId . "' and provider_id = '" . $branchId . "') as reservation,";
        $query .= "(SELECT COUNT(*) FROM reserved_times rt WHERE rt.offer_id = '" . $offerId . "' and rt.day_date = '" . $dayDate . "' and rt.branch_id = '" . $branchId . "' ) as day_reserved";
        $reservation = \Illuminate\Support\Facades\DB::select('SELECT ' . $query . ' FROM DUAL;')[0];

        if ($reservation->day_reserved)
            return -1;
        else
            return $reservation->reservation;

    }

    protected function getOfferTimesInDay($offerId,$branch_id, $dayName, $count = false)
    {
        // effect by date
        $offerTimes = OfferBranchTime::query();
        $offerTimes = $offerTimes->where('offer_id', $offerId)
            ->where('branch_id',$branch_id)
            ->whereRaw('LOWER(day_name) = ?', strtolower($dayName))
            ->orderBy('created_at')->orderBy('order');

        $times = $this->getOfferTimePeriods($offerTimes->get());
        if ($count)
            if (!empty($times) && is_array($times))
                return count($times);
            else
                return 0;

        return $times;
    }


    protected function getOfferReservationInTime($offerId,$branchId, $date, $fromTime, $toTime)
    {
        // effect by date
        return Reservation::where('offer_id', $offerId)->where('provider_id',$branchId)->where('day_date', $date)->where('from_time', $fromTime)->first();
    }


    protected function getOfferTimePeriods($working_days)
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
}
