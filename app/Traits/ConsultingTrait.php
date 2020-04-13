<?php

namespace App\Traits;

use App\Models\Doctor;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Support\Facades\Auth;

trait ConsultingTrait
{
    public function getDoctors($specification_id, $nickname_id, $gender)
    {

        $doctor = Doctor::query();
        $doctor = $doctor->where('doctor_type', 'consultative')
            ->where('specification_id', $specification_id)
            ->with(['specification' => function ($q1) {
                $q1->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);

        if ($nickname_id != null && $nickname_id != 0)
            $doctor = $doctor->where('nickname_id', $nickname_id);

        if ($gender != null && $gender != 0 && in_array($gender, [1, 2]))
            $doctor = $doctor->where('gender', $gender);

        $doctor = $doctor->select('id', 'specification_id', 'photo', 'rate', 'price', 'reservation_period',
            DB::raw('name_' . $this->getCurrentLang() . ' as name')
        );
        return $doctor->where('doctors.status', 1)->paginate(PAGINATION_COUNT);
    }
}
