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
use App\Models\ServiceReservation;
use App\Traits\Dashboard\ReservationTrait;
use App\Traits\CPanel\GeneralTrait;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CPanel\ReservationResource;
use function foo\func;

class ServicesReservationController extends Controller
{
    use GlobalTrait;

    public function index(Request $request)
    {

        if ($request->reservation_id) {
            $reservation = ServiceReservation::find($request->reservation_id);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.Reservation Not Found'));
        }

        $reservations = ServiceReservation::with(['service' => function ($g) {
            $g->select('id', 'specification_id', DB::raw('title_' . app()->getLocale() . ' as title'))
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
        }, 'type' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($qq) {
            $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
        },
        ]);





        if ($request->reservation_id) {
            $reservation = $reservations->find($request->reservation_id);
            $reservation->makeHidden(['paid', 'branch_id', 'provider_id', 'for_me', 'is_reported', 'reservation_total', 'mainprovider', 'rejected_reason_id', 'rejection_reason', 'user_rejection_reason', 'order', 'is_visit_doctor', 'bill_total', 'latitude', 'longitude', 'admin_value_from_reservation_price_Tax']);
            if (!$reservation)
                return $this->returnError('E001', trans('messages.No Reservations founded'));
            else
                return $this->returnData('reservations', $reservation);
        }

        if (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $res = $reservations -> where('reservation_no', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('day_date', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('from_time', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('to_time', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('price', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('total_price', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('bill_total', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('discount_type', 'LIKE', '%' . trim($q) . '%')
                ->orWhereHas('user', function ($query) use ($q) {
                    $query->where('name', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhereHas('service', function ($query) use ($q) {
                    $query->where('title_ar', 'LIKE', '%' . trim($q) . '%') ->where('title_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('paymentMethod', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%') ->where('name_en', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhereHas('branch', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%') ->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('provider', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')->where('name_en', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'خدمة بالمركز الطبي') {
                        $qq->where('service_type', 2);
                    } elseif (trim($q) == 'خدمة منزلية') {
                        $qq->where('service_type', 1);
                    }
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
                ->orderBy('day_date', 'DESC')
                ->paginate(PAGINATION_COUNT);

            $data['reservations'] = new ReservationResource($res);

        }
        else{
            $data['reservations'] = new ReservationResource(Reservation::orderBy('day_date', 'DESC')
                ->paginate(10));
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
                "status" => "required|in:1,2" // 1 == confirmed && 2 == canceled
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $reservation_id = $request->reservation_id;
            $status = $request->status;
            $rejection_reason = $request->reason;

            $reservation = ServiceReservation::where('id', $reservation_id)->with('user')->first();

            if ($reservation == null)
                return response()->json(['success' => false, 'error' => __('messages.No reservation with this number')], 200);
            if ($reservation->approved == 1) {
                return response()->json(['success' => false, 'error' => __('messages.Reservation already approved')], 200);
            }

            if ($reservation->approved == 2) {
                return response()->json(['success' => false, 'error' => __('messages.Reservation already rejected')], 200);
            }

            if ($status != 2 && $status != 1) {
                return response()->json(['success' => false, 'error' => __('messages.status must be 1 or 2')], 200);
            } else {

                if ($status == 2) {
                    if ($rejection_reason == null) {
                        return response()->json(['success' => false, 'error' => __('messages.please enter rejection reason')], 200);
                    }
                }

                $data = [
                    'approved' => $status,
                ];

                if (!empty($rejection_reason))
                    $data['rejection_reason'] = $rejection_reason;

                $reservation->update($data);

                return response()->json(['status' => true, 'msg' => __('messages.reservation status changed successfully')]);
            }

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }
    ##################### End change service reservation status ########################

}
