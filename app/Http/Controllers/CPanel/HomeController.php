<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Doctor;
//use App\Models\OfferBranchTime;
use App\Models\DoctorConsultingReservation;
use App\Models\Reservation;
use App\Models\ServiceReservation;
use App\Models\User;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use PublicTrait;

    public function index()
    {
        /*if (Auth::guard('manager-api')->user()->can('random_drawing')) {
            return redirect()->route('admin.lotteries.drawing');
        }*/

        $data['AllProvidersCount'] = $this->getActiveProviders(true,'providers');
        $data['AllBranchCount'] = $this->getActiveProviders(true,'branches');
        $data['activeDoctorsCount'] = $this->getActiveDoctors(true);
        $data['activeUsersCount'] = $this->getActiveUsers(true);
        $data['allUsersCount'] = User::count();

        $doctorAndOfferTotalCount = Reservation::count();
        $servicesTotalCount = ServiceReservation::count();
        $consultingTotalCount = DoctorConsultingReservation::count();
        $data['totalReservations'] = $doctorAndOfferTotalCount + $servicesTotalCount + $consultingTotalCount;

        $doctorAndOfferPendingCount = Reservation::where('approved', 0)->count();
        $servicesPendingCount = ServiceReservation::where('approved', 0)->count();
        $consultingPendingCount = DoctorConsultingReservation::where('approved', '0')->count();
        $data['pendingReservations'] = $doctorAndOfferPendingCount + $servicesPendingCount + $consultingPendingCount;

        $doctorAndOfferApprovedCount = Reservation::where('approved', 1)->count();
        $servicesApprovedCount = ServiceReservation::where('approved', 1)->count();
        $consultingApprovedCount = DoctorConsultingReservation::where('approved', '1')->count();
        $data['approvedReservations'] = $doctorAndOfferApprovedCount + $servicesApprovedCount + $consultingApprovedCount;

        $doctorAndOfferRefusedByProviderCount = Reservation::where('approved', 2)->where('rejection_reason', '!=', 0)->where('rejection_reason', '!=', '')->where('rejection_reason', '!=', 0)->whereNotNull('rejection_reason')->count(); //rejected  reservations  by providers
        $servicesRefusedByProviderCount = ServiceReservation::where('approved', 2)->whereNotNull('rejection_reason')->where('rejection_reason', '!=', '')->count(); //rejected  reservations  by providers
        $consultingRefusedByProviderCount = DoctorConsultingReservation::where('approved', '2')->count(); //rejected  reservations  by providers
        $data['refusedReservationsByProvider'] = $doctorAndOfferRefusedByProviderCount + $servicesRefusedByProviderCount + $consultingRefusedByProviderCount;

        $doctorAndOfferRefusedByUserCount = Reservation::where('approved', 5)->count();
        $servicesRefusedByUserCount = ServiceReservation::where('approved', 2)->whereNotNull('rejected_reason_notes')->count();
        $consultingRefusedByUserCount = 0;// user not reject consulting reservations
        $data['refusedReservationsByUser'] = $doctorAndOfferRefusedByUserCount + $servicesRefusedByUserCount + $consultingRefusedByUserCount;

        $doctorAndOfferCompleteVisitedCount = Reservation::where('approved', 3)->count();
        $servicesCompleteVisitedCount = ServiceReservation::where('approved', 3)->where('is_visit_doctor', 1)->count();
        $consultingCompleteVisitedCount = DoctorConsultingReservation::whereNotNull('chat_duration')->where('chat_duration', '!=', 0)->where('approved', '3')->count();
        $data['completedReservationsWithVisited'] = $doctorAndOfferCompleteVisitedCount + $servicesCompleteVisitedCount + $consultingCompleteVisitedCount;

        $doctorAndOfferCompleteNotVisitedCount = Reservation::where('approved', 2)->where(function ($q) {
            $q->whereNull('rejection_reason');
            $q->orwhere('rejection_reason', '');
            $q->orwhere('rejection_reason', 0);
        })->count();

        $servicesCompleteNotVisitedCount = ServiceReservation::where('approved', 2)
            ->where(function ($q) {
                $q->whereNull('rejected_reason_notes')
                    ->orwhere('rejected_reason_notes', '=', '')
                    ->orwhere('rejected_reason_notes', 0);
            })
            ->where(function ($q) {
                $q->whereNull('rejection_reason')
                    ->orwhere('rejection_reason', '=', '')
                    ->orwhere('rejection_reason', 0);
            })->count();

        $consultingCompleteNotVisitedCount = DoctorConsultingReservation::where('approved', '3')
            ->where(function ($q) {
                $q->whereNull('chat_duration')
                    ->orwhere('chat_duration', 0);
            })->count();

        $data['completedReservationsWithNotVisited'] = $doctorAndOfferCompleteNotVisitedCount + $servicesCompleteNotVisitedCount + $consultingCompleteNotVisitedCount;

        $allowTime = 15;  // 15 minutes
        $data['reservation_notReplayForMore15Mins'] = Reservation::where('approved', 0)->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)->count();

        $todayReservation = Reservation::today()->count();
        $tommorrow = Reservation::tommorow()->count();
        $data['todayAndTomorrowReservationCount'] = $todayReservation + $tommorrow;

        $userWhereHasReservations = Reservation::distinct('user_id')->count('user_id');
        $data['percentage'] = $data['allUsersCount'] > 0 ? round(($userWhereHasReservations / $data['allUsersCount']) * 100, 2) : 0;
        $data['totalUserNotHasReservations'] = User::whereDoesntHave('reservations')->count();

        $data['TotalUserDownloadAppAndRegister'] = User::where('operating_system', 'android')->orwhere('operating_system', 'ios')->count();
        $data['totalUserRegisterUsingAndroidApp'] = User::where('operating_system', 'android')->count();
        $data['totalUserRegisterUsingIOSApp'] = User::where('operating_system', 'ios')->count();

        $data['mostOffersBooking'] = Reservation::whereNotNull('promocode_id')->groupBy('promocode_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get(['promocode_id', DB::raw('count(promocode_id) as count')])
            ->count();

        $data['mostBookingDay'] = Reservation::groupBy('day_date')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['day_date', DB::raw('count(day_date) as count')]);

        if (isset($data['mostBookingDay']) && $data['mostBookingDay']->count() > 0) {
            foreach ($data['mostBookingDay'] as $day) {
                $day->day_name = __('messages.' . \App\Traits\Dashboard\ReservationTrait::getdayNameByDate($day['day_date']));
                $day->makeHidden(['branch_name', 'for_me', 'branch_no', 'is_reported', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'comment_report', 'reservation_total']);
            }
        }


        $data['mostBookingHour'] = Reservation::groupBy('from_time')
            ->orderBy('count', 'desc')
            ->limit(5)->get(['from_time', 'to_time', DB::raw('count(from_time) as count')]);

        if (isset($data['mostBookingHour']) && $data['mostBookingHour']->count() > 0) {
            foreach ($data['mostBookingHour'] as $hour) {
                $hour->makeHidden(['branch_name', 'for_me', 'branch_no', 'is_reported', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'comment_report', 'reservation_total']);
            }
        }


        $data['mostBookingProviders'] = Reservation::groupBy('provider_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['provider_id', DB::raw('count(provider_id) as count')]);

        if (isset($data['mostBookingProviders']) && $data['mostBookingProviders']->count() > 0) {
            foreach ($data['mostBookingProviders'] as $provider) {
                $provider->makeHidden(['for_me', 'branch_no', 'is_reported', 'admin_value_from_reservation_price_Tax', 'comment_report', 'reservation_total']);
            }
        }

        $data['mostBookingDoctor'] = Reservation::groupBy('doctor_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['doctor_id', DB::raw('count(doctor_id) as count')]);

        if (isset($data['mostBookingDoctor']) && $data['mostBookingDoctor']->count() > 0) {
            foreach ($data['mostBookingDoctor'] as $doctor) {
                $doctor->name = \App\Traits\Dashboard\DoctorTrait::getDoctorNameById($doctor['doctor_id']);
                $doctor->makeHidden(['branch_name', 'for_me', 'branch_no', 'is_reported', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'comment_report', 'reservation_total']);
            }
        }


        $specifications = Doctor::whereHas('reservations')
            ->groupBy('specification_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['id as doctor_id', 'specification_id', DB::raw('count(specification_id) as count')]);

        if (isset($specifications) && $specifications->count() > 0) {
            foreach ($specifications as $specification) {
                $specification->name = \App\Traits\Dashboard\SpecificationTrait::getSpecificationNameById($specification['specification_id']);
                $specification->makeHidden(['branch_name', 'available_time', 'hide', 'times', 'for_me', 'doctor_id', 'branch_no', 'is_reported', 'mainprovider', 'admin_value_from_reservation_price_Tax', 'comment_report', 'reservation_total']);
            }
        }

        $data['allConsultingReservations'] = DoctorConsultingReservation::count();


        $data['approvedReservations'] = Reservation::where('approved', 1)->count(); //approved  reservations

        $data['mostBookingSpecifications'] = $specifications;

        return $this->returnData('data', $data);
    }

    public function search(Request $request)
    {
        $queryStr = $request->queryStr;
        $type = $request->type_id;
        if ($type) {
            if ($type != 'provider' && $type != 'branch' && $type != 'doctor' && $type != 'users') {
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            }
        } else {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }

        $result['page'] = $type;
        $result['queryStr'] = $queryStr;
        return response()->json(['status' => true, 'data' => $result]);

//        $url = "mc33/{$type}/?queryStr=" . $queryStr;
//        return redirect($url);
    }

    public function branchTimes(Request $request)
    {
        $input = $request->only('branchTimes');
        $branches = $input['branchTimes'];
        foreach ($branches as $branchId => $branch) {
            foreach ($branch['days'] as $dayCode => $time) {
//                $this->splitTimes(1, $branchId, $dayCode, $time['from'], $time['to'], $branch['duration']);
//                $returnTimes = $this->splitTimes($time['from'], $time['to'], $branch['duration']);
//                $this->storeOfferBranchTimes(1, $branchId, $dayCode, $returnTimes);
            }
        }

        return response()->json(['status' => true]);

    }

    public function splitTimes($offerId, $branchId, $dayCode, $StartTime, $EndTime, $Duration = "60")
    {
        $returnArray = [];// Define output
        $StartTime = strtotime($StartTime); //Get Timestamp
        $EndTime = strtotime($EndTime); //Get Timestamp

        $addMinutes = $Duration * 60;

        /*for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {
                $returnArray[$i]['from'] = $from;
                $returnArray[$i]['to'] = $to;
            }
        }*/
//        return $returnArray;

        for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {

                OfferBranchTime::create([
                    'offer_id' => $offerId,
                    'branch_id' => $branchId,
                    'day_code' => $dayCode,
                    'time_from' => $from,
                    'time_to' => $to,
                ]);
            }
        }
    }


    public function returnData($key, $value, $msg = "")
    {
        return response()->json(['status' => true, 'errNum' => "S000", 'msg' => $msg, $key => $value]);
    }
    /*public function storeOfferBranchTimes($offerId, $branchId, $dayCode, $times)
    {
        foreach ($times as $key => $value) {
            OfferBranchTime::create([
                'offer_id' => $offerId,
                'branch_id' => $branchId,
                'day_code' => $dayCode,
                'time_from' => $value['from'],
                'time_to' => $value['to'],
            ]);
        }
    }*/
}
