<?php

namespace App\Http\Controllers\CPanel;

use App\Mail\AcceptReservationMail;
use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\Offer;
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
                        ->orWhereHas('doctor', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                        })->orWhereHas('paymentMethod', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                        })
                        ->orWhereHas('branch', function ($query) use ($q) {
                            $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                            $query->orWhereHas('provider', function ($query) use ($q) {
                                $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
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
            $reservation = $this->getReservationByNoWihRelation($request->reservation_id);
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
            return $this->returnSuccessMessage('E001', trans('messages.reservation deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function changeStatus(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "reservation_id" => "required|max:255",
                "status" => "required|in:1,2" // 1 approved 2 reject
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
            if ($reservation->approved == 1 && $request -> status == 1) {
                return $this->returnError('E001', trans('messages.Reservation already approved'));
            }

            if ($reservation->approved == 2 && $request -> status == 2) {
                return $this->returnError('E001', trans('messages.Reservation already rejected'));
            }

            if ($status != 2 && $status != 1) {
                return $this->returnError('E001', trans('messages.status must be 1 or 2'));
            } else {

                if ($status == 2) {
                    if ($rejection_reason == null) {
                        return $this->returnError('E001', trans('messages.please enter rejection reason'));
                    }
                }
                $this->changerReservationStatus($reservation, $status);
                return $this->returnError('E001', trans('messages.reservation status changed successfully'));
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
