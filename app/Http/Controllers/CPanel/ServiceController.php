<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\ServiceTime;
use App\Models\Provider;
use App\Traits\CPanel\ServicesTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalTrait;
use Validator;
use DB;

class ServiceController extends Controller
{
    use GlobalTrait, ServicesTrait, OdooTrait, SMSTrait;

    public
    function index(Request $request)
    {

        $services = Service::with(['specification' => function ($q1) {
            $q1->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'branch' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'), 'provider_id');
        }, 'provider' => function ($q2) {
            $q2->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'types' => function ($q3) {
            $q3->select('services_type.id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }
        ]);

        if (request('queryStr')) {
            $queryStr = request('queryStr');

            $services = $services->where(function ($q) use ($queryStr) {
                $q->where('title_ar', 'LIKE', '%' . trim($queryStr) . '%')
                    ->orwhere('title_en', 'LIKE', '%' . trim($queryStr) . '%');
            });

            //$services = $this->getServices();

        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $services = $services->where('title_ar', 'LIKE', '%' . trim($q) . '%')
                ->orwhere('title_en', 'LIKE', '%' . trim($q) . '%')
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'مفعل') {
                        $qq->where('status', 1);
                    } elseif (trim($q) == 'غير مفعل') {
                        $qq->where('status', 0);
                    }
                })
                ->orWhere(function ($qq) use ($q) {
                    if (trim($q) == 'خدمة منزلية') {
                        $qq->WhereHas('types', function ($query) use ($q) {
                            $query->where('services_type.id', 1);
                        });
                    } elseif (trim($q) == 'خدمة بالمركز الطبي') {
                        $qq->WhereHas('types', function ($query) use ($q) {
                            $query->where('services_type.id', 1);
                        });
                    }
                })
                ->orWhere('clinic_price', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('home_price', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('clinic_price_duration', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('clinic_price_duration', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('home_price_duration', 'LIKE', '%' . trim($q) . '%')
                ->orWhereHas('provider', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                        ->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('branch', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                        ->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('specification', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%')
                        ->orwhere('name_en', 'LIKE', '%' . trim($q) . '%');
                })
                ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%');

        }

        $services = $services->select(
            'id',
            DB::raw('title_' . $this->getCurrentLang() . ' as title'),
            DB::raw('information_' . $this->getCurrentLang() . ' as information')
            , 'specification_id',
            'provider_id', 'branch_id',
            'rate', 'price', 'clinic_price',
            'home_price', 'home_price_duration',
            'clinic_price_duration', 'status', 'reservation_period as clinic_reservation_period'
        )
            ->orderBy('id', 'DESC')
            ->paginate(PAGINATION_COUNT);

        if (count($services) > 0) {
            foreach ($services as $key => $service) {
                $service->time = "";
                $days = $service->times;
            }
            $total_count = $services->total();
            $per_page = PAGINATION_COUNT;
            $services->getCollection()->each(function ($service) {
                $service->makeHidden(['available_time', 'provider_id', 'branch_id']);
                return $service;
            });

            $services = json_decode($services->toJson());
            $servicesJson = new \stdClass();
            $servicesJson->current_page = $services->current_page;
            $servicesJson->total_pages = $services->last_page;
            $servicesJson->total_count = $total_count;
            $servicesJson->per_page = $per_page;
            $servicesJson->data = $services->data;

            return $this->returnData('services', $servicesJson);
        }

        return $this->returnError('D000', __('messages.no services'));
    }

    public function store(Request $request)
    {
        try {

            $rules = [
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "typeIds" => "required|array|min:1",   // 1 -> home 2 -> clinic
                "typeIds.*" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "clinic_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "home_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "clinic_price" => "sometimes|nullable|numeric",  // in minutes
                "home_price" => "sometimes|nullable|numeric",  // in minutes
                "information_en" => "required",
                "information_ar" => "required",
                "payment_method" => "required|array|min:1",
                "working_days" => "required|array|min:1",
                // "clinic_reservation_period" => "sometimes|nullable|numeric",
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
                /* if (empty($request->clinic_reservation_period) or !is_numeric($request->clinic_reservation_period) or $request->clinic_reservation_period < 5) {
                     return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                 }*/

                if (empty($request->clinic_price_duration) or !is_numeric($request->clinic_price_duration)) {
                    return $this->returnError('D000', __('messages.clinic price duration required'));
                }

                if ((empty($request->clinic_price) or !is_numeric($request->clinic_price)) && $request->clinic_price  != 0 ) {
                    return $this->returnError('D000', __('messages.clinic price required'));
                }

                /* if ($request->clinic_reservation_period != $request->clinic_price_duration)
                     return $this->returnError('D000', __('messages.if type is clinic price duration and  reservation period must be equal'));*/

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
                    "status" => 1,
                    "reservation_period" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null,
                    "has_price" => isset($request->has_price)? $request->has_price : null
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

    public function edit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $service = $this->getServicesForEdit($request->service_id);

            if (!$service) {
                return $this->returnError('D000', __('messages.no service with this id'));
            }

            $payment_method = PaymentMethod::where('status', 1)
                ->select(DB::raw('id, flag, name_' . app()->getLocale() . ' as name, IF ((SELECT count(id) FROM service_payment_methods WHERE service_payment_methods.service_id = ' . $service->id . ' AND service_payment_methods.payment_method_id = payment_methods.id) > 0, 1, 0) as selected'))
                ->get();

            $service->time = "";
            $service->selected_payment_method = $payment_method;
            $days = $service->times;
            $service->makeHidden(['available_time', 'provider_id', 'branch_id', 'type']);
            return $this->returnData('service', $service);

        } catch (\Exception $ex) {

            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {

            $rules =  [
                "service_id" => "required|numeric|exists:services,id",
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "typeIds" => "required|array|min:1",   // 1 -> home 2 -> clinic
                "typeIds.*" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "clinic_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "home_price_duration" => "sometimes|nullable||numeric",  // in minutes
                "clinic_price" => "sometimes|nullable|numeric",
                "home_price" => "sometimes|nullable||numeric",
                "information_en" => "required",
                "information_ar" => "required",
                "working_days" => "required|array|min:1",
                "payment_method" => "required|array|min:1",
                "clinic_reservation_period" => "sometimes|nullable|numeric",
            ];

            if (in_array(2, $request->typeIds)) {  // clinic
                $rules["has_price"] = 'required|in:0,1';
            }
            $validator = Validator::make($request->all(),$rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            if (in_array(2, $request->typeIds)) {  // clinic
                /* if (empty($request->clinic_reservation_period) or !is_numeric($request->clinic_reservation_period) or $request->clinic_reservation_period < 5) {
                     return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                 }*/

                if (empty($request->clinic_price_duration) or !is_numeric($request->clinic_price_duration)) {
                    return $this->returnError('D000', __('messages.clinic price duration required'));
                }

                if ((empty($request->clinic_price) or !is_numeric($request->clinic_price)) && $request->clinic_price  != 0 ) {
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
                    "status" => 1,
                    "reservation_period" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null,
                    "has_price" => isset($request->has_price)? $request->has_price : null
                ]);

                $service->times()->delete();
                $service->times()->insert($working_days_data);
                $service->types()->sync($request->typeIds);

                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
                return $this->returnError('D000', __('messages.sorry please try again later'));
            }
            return $this->returnSuccessMessage(trans('messages.service updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            try {
                DB::beginTransaction();

                $service = Service::find($request->service_id);
                if (!$service)
                    return $this->returnError('D000', trans("messages.no service with this id"));

                /* if (count($service->reservations) > 0)
                     return $this->returnError('D000', trans("messages.The service can not be deleted"));*/

                $service->times()->delete();
                $service->delete();

                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
                return $this->returnError($ex->getCode(), $ex->getMessage());
            }
            return $this->returnSuccessMessage(trans('messages.service deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function getAllPaymentMethodWithSelectedListServices()
    {
        $payments = PaymentMethod::where('status', 1)
            ->select('id', 'flag', 'name_' . app()->getLocale() . ' as name', DB::raw('0 as selected'))->get();

        return $this->returnData('payments_methods', $payments);

    }
}
