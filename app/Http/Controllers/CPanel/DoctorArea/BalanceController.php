<?php

namespace App\Http\Controllers\CPanel\DoctorArea;

use App\Http\Resources\CPanel\BalanceResource;
use App\Http\Resources\CPanel\ConsultingBalanceResource;
use App\Http\Resources\CPanel\SingleDoctorBalanceResource;
use App\Http\Resources\CPanel\SingleProviderResource;
use App\Http\Resources\CustomReservationsResource;
use App\Models\Doctor;
use App\Models\DoctorConsultingReservation;
use App\Models\Provider;
use App\Http\Controllers\Controller;
use App\Models\ProviderType;
use App\Models\Reservation;
use App\Models\ServiceReservation;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Validator;

class BalanceController extends Controller
{
    use GlobalTrait;

    //get all reservation doctor - services - offers which cancelled [2 by branch ,5 by user] or complete [3]
    public function getBalanceHistory(Request $request)
    {
        try {
             $doctor = $this->getAuthDoctor();

             if($doctor -> doctor_type =='clinic')
                 return $this->returnError('E001','حسابك تابع لفرع يرجي الرجوع الي الفرع لعرض السجل ');

             $reservations = $this->getReservationBalanceForConsultingDoctors($doctor->id);  // get consulting reservation balance of completed reservation
            if (count($reservations->toArray()) > 0) {
                $reservations->getCollection()->each(function ($reservation) use ($request) {
                    $reservation->makeHidden(['order', 'hours_duration','rejected_reason_type','rejection_resoan','reservation_total', 'admin_value_from_reservation_price_Tax', 'mainprovider', 'is_reported', 'branch_no', 'for_me', 'rejected_reason_id', 'is_visit_doctor', 'rejectionReason', 'user_rejection_reason']);
                    $reservation->reservation_type = 'consulting';
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
            return $this->returnData('reservations', $reservations);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    private function getReservationBalanceForConsultingDoctors($doctorId)
    {
        return $reservations = DoctorConsultingReservation::with(['paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])
            ->whereNull('provider_id')
            ->where('doctor_id', $doctorId)
            ->where('approved', '3')
            ->whereNotNull('chat_duration')
            ->where('chat_duration', '!=', 0)
            ->select('id', 'discount_type', 'hours_duration', 'reservation_no', 'application_balance_value', 'custom_paid_price', 'remaining_price', 'payment_type', 'price', 'bill_total', 'payment_method_id')
            ->orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);
    }


}
