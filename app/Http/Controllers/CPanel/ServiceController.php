<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Controllers\Controller;
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
        $services = $this->getServices();
        if (count($services) > 0) {
            foreach ($services as $key => $service) {
                $service->time = "";
                $days = $service->times;
            }
            $total_count = $services->total();
            $services->getCollection()->each(function ($service) {
                $service->makeHidden(['available_time', 'provider_id', 'branch_id', 'type']);
                return $service;
            });

            $services = json_decode($services->toJson());
            $servicesJson = new \stdClass();
            $servicesJson->current_page = $services->current_page;
            $servicesJson->total_pages = $services->last_page;
            $servicesJson->total_count = $total_count;
            $servicesJson->data = $services->data;

            return $this->returnData('services', $servicesJson);
        }

        return $this->returnError('D000', __('messages.no services'));
    }

    public function store(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "type" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "price" => "required|numeric",
                "information_en" => "required",
                "information_ar" => "required",
                "working_days" => "required|array|min:1",
                "reservation_period" => "sometimes|nullable|numeric",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            if ($request->type == 2 or $request->type == '2') {  // clinic
                if (empty($request->reservation_period) or !is_numeric($request->reservation_period) or $request->reservation_period < 5) {
                    return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
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
                    if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)
                        return $this->returnError('D000', trans("messages.There is one day with incorrect name"));
                    $working_days_data[] = [
                        'provider_id' => $providerId,
                        'branch_id' => $request->branch_id,
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => ($request->type == 2 or $request->type == '2') ? $request->reservation_period : null
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
                    "price" => $request->price,
                    "type" => $request->type,
                    "status" => 1,
                    "reservation_period" => ($request->type == 2 or $request->type == '2') ? $request->reservation_period : null
                ]);

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

            $service->time = "";
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

            $validator = Validator::make($request->all(), [
                "service_id" => "required|numeric|exists:services,id",
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "type" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "price" => "required|numeric",
                "information_en" => "required",
                "information_ar" => "required",
                "working_days" => "required|array|min:1",
                "reservation_period" => "sometimes|nullable|numeric",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            if ($request->type == 2 or $request->type == '2') {  // clinic
                if (empty($request->reservation_period) or !is_numeric($request->reservation_period) or $request->reservation_period < 5) {
                    return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                }
            }

            $service = Service::find($request->service_id);
            if (!$service)
                return $this->returnError('D000', trans("messages.no service with this id"));

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
                    if (!in_array($working_day['day'], $days) || $to->diffInMinutes($from) < $request->reservation_period)
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
                        'reservation_period' => ($request->type == 2 or $request->type == '2') ? $request->reservation_period : null
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
                    "price" => $request->price,
                    "type" => $request->type,
                    "status" => 1,
                    "reservation_period" => ($request->type == 2 or $request->type == '2') ? $request->reservation_period : null
                ]);

                $service->times()->delete();
                $service->times()->insert($working_days_data);

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

                if (count($service->reservations) > 0)
                    return $this->returnError('D000', trans("messages.The service can not be deleted"));

                $service->times()->delete();
                $service->delete();

                DB::commit();
            } catch (\Exception $ex) {
                DB::rollback();
            }
            return $this->returnSuccessMessage(trans('messages.service deleted successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
