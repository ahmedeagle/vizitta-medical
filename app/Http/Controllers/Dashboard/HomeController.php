<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Doctor;
use App\Models\Reservation;
use App\Models\Specification;
use App\Models\User;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use  Str;
use Auth;

class HomeController extends Controller
{
    use PublicTrait;

    public function index()
    {

        if (Auth::guard('web')->user()->can('random_drawing')) {
            return redirect()->route('admin.lotteries.drawing');
        }

        $data['activeProvidersCount'] = $this->getActiveProviders(true);
        $data['activeDoctorsCount'] = $this->getActiveDoctors(true);
        $data['activeUsersCount'] = $this->getActiveUsers(true);
        $data['usersCount'] = User::count();
        //  $data['finishedPaidReservationsCount'] = $this->getFinishedPaidReservations(true);
        // $data['futureReservationsCount'] = $this->getFutureReservations(true);
        // $data['paidReservationsCount'] = $this->getPaidReservations(true);
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


        $todayReservation = Reservation::today()->count();
        $tommorrow = Reservation::tommorow()->count();
        $data['todayAndTomorrowReservationCount'] = $todayReservation + $tommorrow;

        // $reservation_notReplayForMore15Mins= Reservation::notReplay15Min() -> count();
        $allowTime = 15;  // 15 minutes
        $data['reservation_notReplayForMore15Mins'] = Reservation::where('approved', 0)->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)->count();

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

        //
        $allUsers = User::count();
        $userWhereHasReservations = Reservation::distinct('user_id')->count('user_id');
        $data['percentage'] = $allUsers > 0 ? round(($userWhereHasReservations / $allUsers) * 100, 2) : 0;

        $data['totalUserNotHasReservastiond'] = User::whereDoesntHave('reservations')->count();

        $specifications = Doctor::whereHas('reservations')
            ->groupBy('specification_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['id as doctor_id', 'specification_id', DB::raw('count(specification_id) as count')]);

        $data['TotalUserDownloadAppAndRegister'] = User::where('operating_system', 'android')->orwhere('operating_system', 'ios')->count();
        $data['totalUserRegisterUsingAndroidApp'] = User::where('operating_system', 'android')->count();
        $data['totalUserRegisterUsingIOSApp'] = User::where('operating_system', 'ios')->count();

        foreach ($specifications as $specification) {
            $specification->makeVisible(['specification_id']);
            $specification->makeHidden(['hide', 'doctor_id']);
        }
        $data['mostBookingSpecifications'] = $specifications;

        /* ->orderBy('count', 'desc')
         ->get(['doctor_id', DB::raw('count(doctor_id) as count')])->first();*/

        return view('home', $data);
    }


}
