<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Doctor;
//use App\Models\OfferBranchTime;
use App\Models\Reservation;
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

        $data['activeProvidersCount'] = $this->getActiveProviders(true);
        $data['activeDoctorsCount'] = $this->getActiveDoctors(true);
        $data['activeUsersCount'] = $this->getActiveUsers(true);
        $data['allUsersCount'] = User::count();
        $data['totalReservations'] = Reservation::count();

        $data['pendingReservations'] = Reservation::where('approved', 0)->count(); //pending reservations
        $data['approvedReservations'] = Reservation::where('approved', 1)->count(); //approved  reservations
        $data['refusedReservationsByProvider'] = Reservation::where('approved', 2)->where('rejection_reason', '!=', 0)->where('rejection_reason', '!=', '')->where('rejection_reason', '!=', 0)->whereNotNull('rejection_reason')->count(); //rejected  reservations  by providers
        $data['refusedReservationsByUser'] = Reservation::where('approved', 5)->count(); //rejected  reservations by users
        $data['completedReservationsWithVisited'] = Reservation::where('approved', 3)->count(); //completed  reservations with user visit doctor
        $data['completedReservationsWithNotVisited'] = Reservation::where('approved', 2)->where(function ($q) {
            $q->whereNull('rejection_reason');
            $q->orwhere('rejection_reason', '');
            $q->orwhere('rejection_reason', 0);
        })->count(); //completed  reservations with user not visit doctor

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
            ->get(['promocode_id', DB::raw('count(promocode_id) as count')]);

        $data['mostBookingDay'] = Reservation::groupBy('day_date')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['day_date', DB::raw('count(day_date) as count')]);

        $data['mostBookingHour'] = Reservation::groupBy('from_time')
            ->orderBy('count', 'desc')
            ->limit(5)->get(['from_time', 'to_time', DB::raw('count(from_time) as count')]);

        $data['mostBookingProviders'] = Reservation::groupBy('provider_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['provider_id', DB::raw('count(provider_id) as count')]);

        $data['mostBookingDoctor'] = Reservation::groupBy('doctor_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['doctor_id', DB::raw('count(doctor_id) as count')]);

        $specifications = Doctor::whereHas('reservations')
            ->groupBy('specification_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['id as doctor_id', 'specification_id', DB::raw('count(specification_id) as count')]);

        foreach ($specifications as $specification) {
            $specification->makeVisible(['specification_id']);
            $specification->makeHidden(['hide', 'doctor_id']);
        }

        $data['mostBookingSpecifications'] = $specifications;

        return $this -> returnData('data',$data);
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
