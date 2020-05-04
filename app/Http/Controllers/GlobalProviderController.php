<?php

namespace App\Http\Controllers;

use App\Http\Resources\CPanel\MainActiveProvidersResource;
use App\Http\Resources\ProviderServicesResource;
use App\Models\Service;
use App\Models\Provider;
use App\Models\ServiceTime;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ProviderTrait;
use Illuminate\Support\Facades\DB;
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

    public function storeService(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "api_token" => "required",
                "branch_id" => "required|numeric|exists:providers,id",
                "title_ar" => "required|max:255",
                "title_en" => "required|max:255",
                "typeIds" => "required|array|min:1",   // 1 -> home 2 -> clinic
                "typeIds.*" => "required|in:1,2",   // 1 -> home 2 -> clinic
                "specification_id" => "required|exists:specifications,id",
                "price" => "required|numeric",
                "clinic_price_duration" => "sometimes|nullable|numeric",  // in minutes
                "home_price_duration" => "sometimes|nullable||numeric",  // in minutes
                "information_en" => "required",
                "information_ar" => "required",
                "working_days" => "required|array|min:1",
                "clinic_reservation_period" => "sometimes|nullable|numeric",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            if (in_array(2, $request->typeIds)) {  // clinic
                if (empty($request->clinic_reservation_period) or !is_numeric($request->clinic_reservation_period) or $request->clinic_reservation_period < 5) {
                    return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                }

                if (empty($request->clinic_price_duration) or !is_numeric($request->clinic_price_duration)) {
                    return $this->returnError('D000', __('messages.clinic price duration required'));
                }

                if ($request->clinic_reservation_period != $request->clinic_price_duration)
                    return $this->returnError('D000', __('messages.if type is clinic price duration and  reservation period must be equal'));

            }   // price_duration here is equal to  "reservation_period"

            if (in_array(1, $request->typeIds)) {  // home
                /*if (empty($request->home_reservation_period) or !is_numeric($request->home_reservation_period) or $request->home_reservation_period < 5) {
                    return $this->returnError('D000', __('messages.reservation period required and must be numeric'));
                }*/
                if (empty($request->home_price_duration) or !is_numeric($request->home_price_duration)) {
                    return $this->returnError('D000', __('messages.home price duration required'));
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
                    if (!in_array($working_day['day'], $days) || (in_array(2, $request->typeIds) && $to->diffInMinutes($from) < $request->clinic_reservation_period))
                        return $this->returnError('D000', trans("messages.There is one day with incorrect name"));
                    $working_days_data[] = [
                        'provider_id' => $providerId,
                        'branch_id' => $request->branch_id,
                        'day_name' => strtolower($working_day['day']),
                        'day_code' => substr(strtolower($working_day['day']), 0, 3),
                        'from_time' => $from->format('H:i'),
                        'to_time' => $to->format('H:i'),
                        'order' => array_search(strtolower($working_day['day']), $days),
                        'reservation_period' => in_array(2, $request->typeIds) ? $request->clinic_reservation_period : null
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
                    "clinic_price_duration" => in_array(2, $request->typeIds) ? $request->clinic_price_duration : null,
                    "home_price_duration" => in_array(1, $request->typeIds) ? $request->home_price_duration : null,
                    "status" => 1,
                    "reservation_period" => in_array(2, $request->typeIds) ? $request->clinic_reservation_period : null
                ]);

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

}
