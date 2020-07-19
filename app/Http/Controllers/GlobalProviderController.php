<?php

namespace App\Http\Controllers;

use App\Http\Resources\CPanel\MainActiveProvidersResource;
use App\Http\Resources\CustomReservationsResource;
use App\Http\Resources\ProviderServicesResource;
use App\Models\DoctorConsultingReservation;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Provider;
use App\Models\ServiceReservation;
use App\Models\ServiceTime;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Http\Request;
use App\Traits\ProviderTrait;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Schema;
use Validator;
use Auth;
use Mail;
use JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use DateTime;

class GlobalProviderController extends Controller
{
    use ProviderTrait, GlobalTrait, DoctorTrait, SMSTrait, OdooTrait;

    public function __construct(Request $request)
    {

    }

    ############################ Start Services Section ###########################

    public function getProviderServices(Request $request)
    {
        try {
            $requestData = $request->all();
            $rules = [
                "service_type" => "nullable|in:1,2",
                "api_token" => "required",
            ];
            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);

            $type = $request->service_type;
            if (!$provider)
                return $this->returnError('E001', trans('messages.No provider with this id'));

            if (empty($type)) {
                $services = Service::with('types')->whereHas('provider', function ($q) use ($provider) {
                    $q->where('id', $provider->id);
                })->orderBy('id', 'DESC')
                    ->paginate(PAGINATION_COUNT);
            } else {
                $services = Service::whereHas('types', function ($q) use ($type) {
                    $q->where('type_id', $type);
                })->whereHas('provider', function ($q) use ($provider) {
                    $q->where('id', $provider->id);
                })->orderBy('id', 'DESC')
                    ->paginate(PAGINATION_COUNT);
            }

            $result = new ProviderServicesResource($services);

            return $this->returnData('services', $result);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function getAllProviderBranchesList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "api_token" => "required",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);
            $branches = Provider::where('status', true)->where('provider_id', $provider->id)->get();

            $result = MainActiveProvidersResource::collection($branches);
            return $this->returnData('branches', $result);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function checkHomeVisits(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "api_token" => "required",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);
            if ($provider->provider_id == null) // main providers
                return $this->returnData('has_home_visits', $provider->has_home_visit);
            else  // branch
                return $this->returnData('has_home_visits', $provider->provider->has_home_visit);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }

    }

    public function storeService(Request $request)
    {
        try {

            $rules = [
                "api_token" => "required",
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "typeIds" => "required|array|min:1",   // 1 -> home 2 -> clinic
                "typeIds.*" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "clinic_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "home_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "clinic_price" => "sometimes|nullable|numeric",
                "home_price" => "sometimes|nullable|numeric",
                "information_en" => "required",
                "information_ar" => "required",
                "payment_method" => "required|array|min:1",
                "working_days" => "required|array|min:1",
                "clinic_reservation_period" => "sometimes|nullable|numeric",
            ];
            if (in_array(2, $request->typeIds)) {  // clinic
                $rules["has_price"] = 'required|in:0,1';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            if (in_array(2, $request->typeIds)) {  // clinic


                if (empty($request->clinic_price_duration) or !is_numeric($request->clinic_price_duration)) {
                    return $this->returnError('D000', __('messages.clinic price duration required'));
                }


                if ((empty($request->clinic_price) or !is_numeric($request->clinic_price)) && $request -> clinic_price !=0) {
                    return $this->returnError('D000', __('messages.clinic price required'));
                }


            }   // price_duration here is equal to  "reservation_period"

            if (in_array(1, $request->typeIds)) {  // home
                /*if (empty($request->home_reservation_period) or !is_numeric($request->home_reservation_period) or $request->home_reservation_period < 5) {
                    return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                }*/
                if (empty($request->home_price_duration) or !is_numeric($request->home_price_duration)) {
                    return $this->returnError('D000', __('messages.home price duration required'));
                }

                if (empty($request->home_price) or !is_numeric($request->home_price)) {
                    return $this->returnError('D000', __('messages.home price required'));
                }
            }

            $branch = Provider::whereNotNull('provider_id')->find($request->branch_id);
            if (!$branch)
                return $this->returnError('D000', __('messages.service must be for branch id'));

            try {
                DB::beginTransaction();

                $provider = $this->getData($request->api_token);
                $providerId = $provider->id;

                // working days
                $working_days_data = [];
                $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                foreach ($request->working_days as $working_day) {
                    $from = Carbon::parse($working_day['from']);
                    $to = Carbon::parse($working_day['to']);
                    if (!in_array($working_day['day'], $days) || (in_array(2, $request->typeIds) && $to->diffInMinutes($from) < $request->clinic_price_duration))
                        return $this->returnError('D000', trans("messages.There is one day with incorrect name"));
                    $working_days_data[] = [
                        'provider_id' => $providerId,
                        'branch_id' => $request->branch_id,
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null
                    ];
                }

                $service = Service::create([
                    "title_ar" => $request->title_ar,
                    "title_en" => $request->title_en,
                    "information_ar" => $request->information_ar,
                    "information_en" => $request->information_en,
                    "branch_id" => $request->branch_id,
                    "provider_id" => $providerId,
                    "specification_id" => $request->specification_id,
                    "home_price" => in_array(1, $request->typeIds) ? $request->home_price : null,
                    "clinic_price" => in_array(2, $request->typeIds) ? $request->clinic_price : null,
                    "clinic_price_duration" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null,
                    "home_price_duration" => in_array(1, $request->typeIds) ? $request->home_price_duration : null,
                    "status" => 0,
                    "has_price" => isset($request->has_price)? $request->has_price : null,
                    "reservation_period" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null
                ]);

                if (isset($request->payment_method) && !empty($request->payment_method)) {
                    foreach ($request->payment_method as $k => $method) {
                        $service->paymentMethods()->attach($method['payment_method_id'], ['payment_amount_type' => $method['payment_amount_type'], 'payment_amount' => $method['payment_amount']]);
                    }
                }

                $service->types()->attach($request->typeIds);

                for ($i = 0; $i < count($working_days_data); $i++) {
                    $working_days_data[$i]['service_id'] = $service->id;
                }
                ServiceTime::insert($working_days_data);
                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
                return $this->returnError('D000', __('messages.sorry please try again later'));
            }

            return $this->returnSuccessMessage(trans('messages.Service added successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function destroyService(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
                "api_token" => "required",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);
            $service = Service::where('provider_id', $provider->id)->find($request->service_id);

            if (!$service)
                return $this->returnError('D000', __('messages.service not found'));

            if ($service->reservations()->count() > 0)
                return $this->returnError('D000', __('messages.can not delete service with reservations'));
            else
                $service->delete();

            return $this->returnSuccessMessage(trans('messages.Service deleted successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function editService(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
                "api_token" => "required",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $provider = $this->getData($request->api_token);
            $service = $this->getServicesForEdit($request->service_id, $provider->id);
            if (!$service) {
                return $this->returnError('D000', __('messages.no service with this id'));
            }

            $payment_method = PaymentMethod::where('status', 1)
                ->select(DB::raw('id, flag, name_' . app()->getLocale() . ' as name, IF ((SELECT count(id) FROM service_payment_methods WHERE service_payment_methods.service_id = ' . $service->id . ' AND service_payment_methods.payment_method_id = payment_methods.id) > 0, 1, 0) as selected'))
                ->get();
            $service->selected_payment_method = $payment_method;

            $service->time = "";
            $days = $service->times;
            $service->makeHidden(['available_time', 'provider_id', 'branch_id', 'type']);
            return $this->returnData('service', $service);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function updateService(Request $request)
    {
        try {

            $rules=[
                "service_id" => "required|numeric|exists:services,id",
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "typeIds" => "required|array|min:1",   // 1 -> home 2 -> clinic
                "typeIds.*" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "clinic_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "home_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "clinic_price" => "sometimes|nullable|numeric",
                "home_price" => "sometimes|nullable|numeric",
                "information_en" => "required",
                "information_ar" => "required",
                "payment_method" => "required|array|min:1",
                "working_days" => "required|array|min:1",
                "clinic_reservation_period" => "sometimes|nullable|numeric",
                "api_token" => "required",
            ];
            if (in_array(2, $request->typeIds)) {  // clinic
                $rules["has_price"] = 'required|in:0,1';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $provider = $this->getData($request->api_token);

            if (in_array(2, $request->typeIds)) {  // clinic

                if (empty($request->clinic_price_duration) or !is_numeric($request->clinic_price_duration)) {
                    return $this->returnError('D000', __('messages.clinic price duration required'));
                }

                if ((empty($request->clinic_price) or !is_numeric($request->clinic_price)) && $request -> clinic_price !=0) {
                    return $this->returnError('D000', __('messages.clinic price required'));
                }
            }   // price_duration here is equal to  "reservation_period"

            if (in_array(1, $request->typeIds)) {  // home
                /*if (empty($request->home_reservation_period) or !is_numeric($request->home_reservation_period) or $request->home_reservation_period < 5) {
                    return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                }*/
                if (empty($request->home_price_duration) or !is_numeric($request->home_price_duration)) {
                    return $this->returnError('D000', __('messages.home price duration required'));
                }
                if (empty($request->home_price) or !is_numeric($request->home_price)) {
                    return $this->returnError('D000', __('messages.home price required'));
                }
            }

            $service = Service::find($request->service_id);
            if (!$service)
                return $this->returnError('D000', trans("messages.no service with this id"));


            if (isset($request->payment_method) && !empty($request->payment_method)) {
                $service->paymentMethods()->detach();
                foreach ($request->payment_method as $k => $method) {
                    $service->paymentMethods()->attach($method['payment_method_id'], ['payment_amount_type' => $method['payment_amount_type'], 'payment_amount' => $method['payment_amount']]);
                }
            }

            $branch = Provider::whereNotNull('provider_id')->find($request->branch_id);
            if (!$branch)
                return $this->returnError('D000', __('messages.service must be for branch id'));

            try {
                DB::beginTransaction();

                $provider = Provider::find($request->branch_id);
                $providerId = $provider->provider_id;
                // working days
                $working_days_data = [];
                $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

                foreach ($request->working_days as $working_day) {
                    $from = Carbon::parse($working_day['from']);
                    $to = Carbon::parse($working_day['to']);
                    if (!in_array($working_day['day'], $days) || (in_array(2, $request->typeIds) && $to->diffInMinutes($from) < $request->clinic_price_duration))
                        return $this->returnError('D000', trans("messages.There is one day with incorrect name"));

                    $working_days_data[] = [
                        'service_id' => $request->service_id,
                        'provider_id' => $providerId,
                        'branch_id' => $request->branch_id,
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null
                    ];

                }

                $service->update([
                    "title_ar" => $request->title_ar,
                    "title_en" => $request->title_en,
                    "information_ar" => $request->information_ar,
                    "information_en" => $request->information_en,
                    "branch_id" => $request->branch_id,
                    "provider_id" => $providerId,
                    "specification_id" => $request->specification_id,
                    "home_price" => in_array(1, $request->typeIds) ? $request->home_price : null,
                    "clinic_price" => in_array(2, $request->typeIds) ? $request->clinic_price : null,
                    "clinic_price_duration" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null,
                    "home_price_duration" => in_array(1, $request->typeIds) ? $request->home_price_duration : null,
                    //"status" => 0,
                    "reservation_period" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null,
                    "has_price" => isset($request->has_price)? $request->has_price : null
                ]);

                $service->times()->delete();
                $service->times()->insert($working_days_data);
                $service->types()->sync($request->typeIds);

                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
//                return $ex;
                return $this->returnError('D000', __('messages.sorry please try again later'));
            }
            return $this->returnSuccessMessage(trans('messages.service updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function toggleService(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
                "api_token" => "required",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = $this->getData($request->api_token);
            $service = Service::where('provider_id', $provider->id)->find($request->service_id);

            if (!$service)
                return $this->returnError('D000', __('messages.service not found'));

            $newStatus = $service->status == 0 ? 1 : 0; // 0 => not active && 1=> active
            $service->update([
                'status' => $newStatus,
            ]);

            $result = ['status' => $newStatus];

            if ($newStatus == 0)
                return $this->returnData('service', $result, trans('messages.The service was hidden successfully'));
            else
                return $this->returnData('service', $result, trans('messages.The service was shown successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getServicesForEdit($id = null, $providerId = null)
    {
        $services = Service::query();
        $services = $services->with(['specification' => function ($q1) {
            $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
        }, 'provider' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'types'
        ]);


        if ($id != null)
            $services = $services->where('id', $id)->where('provider_id', $providerId);

        $services = $services->select(
            'id',
            'title_ar',
            'title_en',
            'information_ar',
            'information_en',
            'specification_id',
            'provider_id',
            'branch_id',
            'rate',
            'home_price',
            'price',
            'has_price',
            'home_price',
            'clinic_price',
            'home_price_duration',
            'clinic_price_duration',
            'status',
            'reservation_period as clinic_reservation_period'
        );

        if ($id != null)
            return $services->first();
        else
            return $services->paginate(PAGINATION_COUNT);
    }

    ############################ End Services Section #############################


    ############################ Start Consulting Section ###########################

    public function getProviderCurrentConsultingReservations(Request $request)
    {
        try {
            $provider = $this->getData($request->api_token);
            $consultings = $this->getCurrentReservations($provider->id);

            if (isset($consultings) && $consultings->count() > 0) {
                foreach ($consultings as $key => $consulting) {
                    $consulting_start_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->from_time));
                    $consulting_end_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->to_time));
                    $consulting->consulting_start_date = $consulting_start_date;
                    $consulting->consulting_end_date = $consulting_end_date;
                    //return $consulting_start_date .' > = '.date('Y-m-d H:i:s');
                    if (date('Y-m-d H:i:s') >= $consulting_start_date && ($this->getDiffBetweenTwoDate(date('Y-m-d H:i:s'), $consulting_start_date) <= $consulting->hours_duration)) {
                        $consulting->allow_chat = 1;
                    } else {
                        $consulting->allow_chat = 0;
                    }
                    $consulting->makeHidden(['day_date', 'from_time', 'to_time', 'rejected_reason_type', 'reservation_total', 'for_me', 'is_reported', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax']);
                    $consulting->doctor->makeHidden(['times']);
                }
            }

            if (count($consultings->toArray()) > 0) {
                $total_count = $consultings->total();
                $consultings = json_decode($consultings->toJson());
                $consultingsJson = new \stdClass();
                $consultingsJson->current_page = $consultings->current_page;
                $consultingsJson->total_pages = $consultings->last_page;
                $consultingsJson->total_count = $total_count;
                $consultingsJson->per_page = PAGINATION_COUNT;
                $consultingsJson->data = $consultings->data;
                return $this->returnData('reservations', $consultingsJson);
            }
            return $this->returnError('E001', trans('messages.No medical consulting founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getCurrentReservations($id)
    {
        $provider = Provider::find($id);

        if ($provider != null) {
            if ($provider->provider_id != null) {
                $branchesIDs = [$provider->id];
            } else {
                $branchesIDs = $provider->providers()->pluck('id');
            }
        }

        return DoctorConsultingReservation::current()
            ->with([
                'doctor' => function ($q) {
                    $q->select('id', 'photo', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))->with(['specification' => function ($qq) {
                        $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
                }, 'paymentMethod' => function ($qu) {
                    $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }])
//            ->where('user_id', $id)
            ->whereIn('provider_id', $branchesIDs)
            //->where('day_date', '>=', Carbon::now()
            //  ->format('Y-m-d'))
            ->orderBy('day_date')
            ->orderBy('order')
            ->select('id', 'doctor_id', 'payment_method_id', 'total_price', 'hours_duration', 'day_date', 'from_time', 'to_time')
            ->paginate(PAGINATION_COUNT);
    }

    public function getProviderFinishedConsultingReservations(Request $request)
    {
        try {
            $provider = $this->getData($request->api_token);
            $consultings = $this->getFinishedReservations($provider->id);
            if (isset($consultings) && $consultings->count() > 0) {
                foreach ($consultings as $key => $consulting) {
                    $consulting->allow_chat = 0;
                    $consulting->makeHidden([ 'rejected_reason_type', 'reservation_total', 'for_me', 'is_reported', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax']);
                    $consulting->doctor->makeHidden(['times']);
                }
            }

            if (count($consultings->toArray()) > 0) {
                $total_count = $consultings->total();
                $consultings = json_decode($consultings->toJson());
                $consultingsJson = new \stdClass();
                $consultingsJson->current_page = $consultings->current_page;
                $consultingsJson->total_pages = $consultings->last_page;
                $consultingsJson->total_count = $total_count;
                $consultingsJson->per_page = PAGINATION_COUNT;
                $consultingsJson->data = $consultings->data;
                return $this->returnData('reservations', $consultingsJson);
            }
            return $this->returnError('E001', trans('messages.No medical consulting founded'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getFinishedReservations($id)
    {

        $provider = Provider::find($id);

        if ($provider != null) {
            if ($provider->provider_id != null) {
                $branchesIDs = [$provider->id];
            } else {
                $branchesIDs = $provider->providers()->pluck('id');
            }
        }
        return DoctorConsultingReservation::finished()
            ->with([
                'user' => function ($u) {
                    $u->select('id', 'name', 'photo');
                },
                'doctor' => function ($q) {
                    $q->select('id', 'photo', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))->with(['specification' => function ($qq) {
                        $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
                }, 'paymentMethod' => function ($qu) {
                    $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }])
//            ->where('user_id', $id)
            ->whereIn('provider_id', $branchesIDs)
            //->where('day_date', '>=', Carbon::now()
            //  ->format('Y-m-d'))
            ->orderBy('day_date')
            ->orderBy('order')
            ->select('id', 'doctor_id', 'chatId', 'payment_method_id', 'total_price', 'hours_duration', 'day_date', 'user_id', 'day_date', 'from_time', 'to_time', 'doctor_rate', 'rate_comment', 'rate_date')
            ->paginate(PAGINATION_COUNT);
    }

    ############################ End Consulting Section #############################

    ############################ Start reservations-record Section #############################

    public function getAllReservationsRecord(Request $request)
    {
        try {
            $provider = $this->getData($request->api_token);
            $reservationType = $request->filter_type;

            if ($provider->provider_id == null) { //main provider
                $branches = $provider->providers()->pluck('id')->toArray();
                array_unshift($branches, $provider->id);
            } else {
                $branches = [$provider->id];
            }
            $result = [];

            if ($reservationType == 0) {  ### All Reservations
                $result = $this->getAllCustomReservations($provider, $request, $branches);
                $result['total_price'] = $this->calcAllReservationAmount($request, $provider->id, $branches, $reservationType);
            } elseif ($reservationType == 1) { ### offers reservations
                $result = $this->getOffersCustomReservations($request, $branches);
                $result['total_price'] = $this->calcAllReservationAmount($request, $provider->id, $branches, $reservationType);
            } elseif ($reservationType == 2) { ### consulting reservations
                $result = $this->getConsultingCustomReservations($request, $provider);
                $result['total_price'] = $this->calcAllReservationAmount($request, $provider->id, $branches, $reservationType);
            } elseif ($reservationType == 3 || $reservationType == 4) { ### home & clinic reservations
                $serviceType = $reservationType == 3 ? 1 : 2; // 1 == home & 2 == clinic reservations
                $result = $this->getServiceCustomReservations($request, $provider, $serviceType);
                $result['total_price'] = $this->calcAllReservationAmount($request, $provider->id, $branches, $reservationType, $serviceType);
            }

            return $this->returnData('reservations', $result);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getAllCustomReservations($provider, $request, $branches = [])
    {
        $reservationCondition = $this->approvedReservationCondition(new Reservation, $request);
        $reservations = $reservationCondition->whereIn('provider_id', $branches)
            ->select("id", "reservation_no", "day_date", "from_time", "to_time", "approved", "is_visit_doctor", DB::raw("'' as provider_id"), DB::raw("provider_id as branch_id") /*, "provider_id", DB::raw("'' as branch_id")*/, "payment_method_id", "doctor_id", "promocode_id", DB::raw("'' as service_id"), DB::raw("'' as service_type"));

        $consultingCondition = $this->approvedReservationCondition(new DoctorConsultingReservation, $request);
        $consulting = $consultingCondition->where(function ($q) use ($provider) {
            $q->where('provider_id', $provider->id)
                ->orWhere('branch_id', $provider->id);
        })->select("id", "reservation_no", "day_date", "from_time", "to_time", "approved", "is_visit_doctor", "provider_id", "branch_id", "payment_method_id", "doctor_id", DB::raw("'' as promocode_id"), DB::raw("'' as service_id"), DB::raw("'' as service_type"));

        $serviceCondition = $this->approvedReservationCondition(new ServiceReservation, $request);
        $services = $serviceCondition->where(function ($q) use ($provider) {
            $q->where('provider_id', $provider->id)
                ->orWhere('branch_id', $provider->id);
        })->select("id", "reservation_no", "day_date", "from_time", "to_time", "approved", "is_visit_doctor", "provider_id", "branch_id", "payment_method_id", DB::raw("'' as doctor_id"), DB::raw("'' as promocode_id"), "service_id", "service_type")
            ->union($reservations)
            ->union($consulting)
            ->paginate(PAGINATION_COUNT);

        $result['data'] = new CustomReservationsResource($services);
        return $result;
    }

    public function getOffersCustomReservations($request, $branches = [])
    {
        $reservationCondition = $this->approvedReservationCondition(new Reservation, $request);
        $reservations = $reservationCondition->whereNotNull('promocode_id')
            ->whereIn('provider_id', $branches)
            ->select("id", "reservation_no", "day_date", "from_time", "to_time", "approved", "is_visit_doctor", DB::raw("'' as provider_id"), DB::raw("provider_id as branch_id") /*, "provider_id", DB::raw("'' as branch_id")*/, "payment_method_id", "doctor_id", "promocode_id", DB::raw("'' as service_id"), DB::raw("'' as service_type"))
            ->paginate(PAGINATION_COUNT);

        $result['data'] = new CustomReservationsResource($reservations);
        return $result;
    }

    public function getConsultingCustomReservations($request, $provider)
    {
        $consultingCondition = $this->approvedReservationCondition(new DoctorConsultingReservation, $request);
        $consulting = $consultingCondition->where(function ($q) use ($provider) {
            $q->where('provider_id', $provider->id)
                ->orWhere('branch_id', $provider->id);
        })->select("id", "reservation_no", "day_date", "from_time", "to_time", "approved", "is_visit_doctor", "provider_id", "branch_id", "payment_method_id", "doctor_id", DB::raw("'' as promocode_id"), DB::raw("'' as service_id"), DB::raw("'' as service_type"))
            ->paginate(PAGINATION_COUNT);

        $result['data'] = new CustomReservationsResource($consulting);
        return $result;
    }

    public function getServiceCustomReservations($request, $provider, $serviceType = '')
    {
        $serviceCondition = $this->approvedReservationCondition(new ServiceReservation, $request);
        $services = $serviceCondition->where('service_type', $serviceType)
            ->where(function ($q) use ($provider) {
                $q->where('provider_id', $provider->id)
                    ->orWhere('branch_id', $provider->id);
            })->select("id", "reservation_no", "day_date", "from_time", "to_time", "approved", "is_visit_doctor", "provider_id", "branch_id", "payment_method_id", DB::raw("'' as doctor_id"), DB::raw("'' as promocode_id"), 'service_id', 'service_type')
            ->paginate(PAGINATION_COUNT);

        $result['data'] = new CustomReservationsResource($services);
        return $result;
    }

    public function calcAllReservationAmount($request, $providerId, $branches = [], $reservationType = 0, $serviceType = '')
    {
        $result = 0;
        if ($reservationType == 0) { ### All Reservations

            $reservationCondition = $this->approvedReservationAmountCondition(new Reservation, $request);
            $reservations = $reservationCondition->whereIn('provider_id', $branches)->sum('price');

            $consultingCondition = $this->approvedReservationAmountCondition(new DoctorConsultingReservation, $request);
            $consulting = $consultingCondition->where(function ($q) use ($providerId) {
                $q->where('provider_id', $providerId)
                    ->orWhere('branch_id', $providerId);
            })->sum('total_price');

            $serviceCondition = $this->approvedReservationAmountCondition(new ServiceReservation, $request);
            $services = $serviceCondition->where(function ($q) use ($providerId) {
                $q->where('provider_id', $providerId)
                    ->orWhere('branch_id', $providerId);
            })->sum('total_price');

            $result = floatval($reservations) + floatval($consulting) + floatval($services);

        } elseif ($reservationType == 1) { ### offers reservations

            $reservationCondition = $this->approvedReservationAmountCondition(new Reservation, $request);
            $result = $reservationCondition->whereIn('provider_id', $branches)
                ->whereNotNull('promocode_id')->sum('price');

        } elseif ($reservationType == 2) { ### consulting reservations

            $consultingCondition = $this->approvedReservationAmountCondition(new DoctorConsultingReservation, $request);
            $result = $consultingCondition->where(function ($q) use ($providerId) {
                $q->where('provider_id', $providerId)
                    ->orWhere('branch_id', $providerId);
            })->sum('total_price');

        } elseif ($reservationType == 3 || $reservationType == 4) { ### home & clinic reservations

            $serviceCondition = $this->approvedReservationAmountCondition(new ServiceReservation, $request);
            $result = $serviceCondition->where('service_type', $serviceType)->where(function ($q) use ($providerId) {
                $q->where('provider_id', $providerId)
                    ->orWhere('branch_id', $providerId);
            });

            if ($reservationType == 4) {
                $result = $result->sum('price');
            } else {
                $result = $result->sum('total_price');
            }

        }

        return number_format((float)$result, 2, '.', '');
    }

    public function approvedReservationCondition($model, $request)
    {
        return $model->where(function ($query) {
            $query->where('approved', '2'); // canceled
            $query->orWhere(function ($q) {
                $q->where('approved', '3');// completed
                $q->where(function ($w) {
                    $w->where('is_visit_doctor', '1')->orWhere('is_visit_doctor', '0');
                });
            });
        })->where(function ($q) use ($model, $request) { ### Search Queries

            if (!is_null($request->search_from_date) && !empty($request->search_from_date) && !is_null($request->search_to_date) && !empty($request->search_to_date)) {
                $q->whereBetween('day_date', [$request->search_from_date, $request->search_to_date]);
            }

            if (!is_null($request->search_payment_method_id) && !empty($request->search_payment_method_id)) {
                $q->where('payment_method_id', $request->search_payment_method_id);
            }

            if (!is_null($request->reservation_no) && !empty($request->reservation_no)) {
                $q->where('reservation_no', $request->reservation_no);
            }

            if (!is_null($request->search_doctor_id) && !empty($request->search_doctor_id)) {
                if (Schema::hasColumn($model->getTable(), 'doctor_id')) {
                    $q->where('doctor_id', $request->search_doctor_id);
                }
            }

            if (!is_null($request->search_branch_id) && !empty($request->search_branch_id)) {
                if (Schema::hasColumn($model->getTable(), 'branch_id'))
                    $q->where('branch_id', $request->search_branch_id);
                else
                    $q->where('provider_id', $request->search_branch_id);
            }

        });
    }

    public function approvedReservationAmountCondition($model, $request)
    {
        return $model->where(function ($q) {
            $q->where('approved', '3');// completed
            $q->where('is_visit_doctor', '1'); // the client visit doctor
        })->where(function ($q) use ($model, $request) { ### Search Queries

            if (!is_null($request->search_from_date) && !empty($request->search_from_date) && !is_null($request->search_to_date) && !empty($request->search_to_date)) {
                $q->whereBetween('day_date', [$request->search_from_date, $request->search_to_date]);
            }

            if (!is_null($request->search_payment_method_id) && !empty($request->search_payment_method_id)) {
                $q->where('payment_method_id', $request->search_payment_method_id);
            }

            if (!is_null($request->reservation_no) && !empty($request->reservation_no)) {
                $q->where('reservation_no', $request->reservation_no);
            }

            if (!is_null($request->search_doctor_id) && !empty($request->search_doctor_id)) {
                if (Schema::hasColumn($model->getTable(), 'doctor_id')) {
                    $q->where('doctor_id', $request->search_doctor_id);
                }
            }

            if (!is_null($request->search_branch_id) && !empty($request->search_branch_id)) {
                if (Schema::hasColumn($model->getTable(), 'branch_id'))
                    $q->where('branch_id', $request->search_branch_id);
                else
                    $q->where('provider_id', $request->search_branch_id);
            }

        });
    }

    ############################ End reservations-record Section #############################

}
