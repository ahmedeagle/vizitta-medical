<?php

namespace App\Http\Controllers;

use App\Http\Resources\CPanel\MainActiveProvidersResource;
use App\Http\Resources\ProviderServicesResource;
use App\Models\Service;
use App\Models\Provider;
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

}
