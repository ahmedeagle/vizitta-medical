<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProviderServicesResource;
use App\Mail\AcceptReservationMail;
use App\Mail\RejectReservationMail;
use App\Models\CommentReport;
use App\Models\Doctor;
use App\Models\PromoCode;
use App\Models\Reason;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\Replay;
use App\Models\Provider;
use App\Models\ReportingType;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Token;
use App\Models\UserAttachment;
use App\Models\UserRecord;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ProviderTrait;
use App\Mail\NewReplyMessageMail;
use App\Mail\NewUserMessageMail;
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
            ];
            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->service_type;
            $provider = Provider::whereNull('provider_id')->find($request->provider_id);
            if (!$provider)
                return $this->returnError('E001', trans('messages.No provider with this id'));

            if (empty($type)) {
                $services = Service::with('types')->whereHas('provider', function ($q) use ($provider) {
                    $q->where('id', $provider->id);
                })->orderBy('id', 'DESC')
                    ->paginate(PAGINATION_COUNT);
            } else {
                $services = Service::whereHas('types', function ($q) use($type) {
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

}
