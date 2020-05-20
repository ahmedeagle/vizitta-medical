<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\BalanceResource;
use App\Http\Resources\CPanel\ConsultingBalanceResource;
use App\Http\Resources\CPanel\SingleDoctorBalanceResource;
use App\Http\Resources\CPanel\SingleProviderResource;
use App\Models\Doctor;
use App\Models\Provider;
use App\Http\Controllers\Controller;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use Validator;

class BalanceController extends Controller
{
    use GlobalTrait;

    public function getBranchesBalances()
    {
        $providers = Provider::with(['provider' => function ($q) {
            $q->select('id', 'name_' . app()->getLocale() . ' as name');
        }])
            ->whereNotNull('provider_id')
            ->select('id', 'name_' . app()->getLocale() . ' as name', 'provider_id', 'balance')
            ->paginate(PAGINATION_COUNT);

        $result = new BalanceResource($providers);
        return $this->returnData('balances', $result);
    }

    public function editBranchBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "branch_id" => "required|numeric|exists:providers,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = Provider::select('id', 'name_' . app()->getLocale() . ' as name', 'balance')->find($request->branch_id);
            $result = new SingleProviderResource($provider);
            return $this->returnData('branch', $result);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function updateBranchBalance(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "branch_id" => "required|numeric|exists:providers,id",
                "balance" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $provider = Provider::whereNotNull('provider_id')->find($request->branch_id);

            if (!$provider)
                return $this->returnError('E001', trans("messages.provider not found"));

            $provider->update(['balance' => $request->balance]);

            return $this->returnSuccessMessage(trans('messages.Balance updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getDoctorsBalances()
    {
         $doctors = Doctor::with(['provider' => function ($q) {
            $q->select('id', 'name_' . app()->getLocale() . ' as name','provider_id');
        }])
            ->select('id', 'name_' . app()->getLocale() . ' as name', 'photo', 'provider_id', 'balance', 'doctor_type', 'is_consult')
            ->paginate(PAGINATION_COUNT);

        $result = new ConsultingBalanceResource($doctors);
        return $this->returnData('balances', $result);
    }

    public function editDoctorsBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "doctor_id" => "required|numeric|exists:doctors,id",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = Doctor::select('id', 'name_' . app()->getLocale() . ' as name', 'balance')->find($request->doctor_id);
            $result = new SingleDoctorBalanceResource($doctor);
            return $this->returnData('doctor', $result);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function updateDoctorsBalance(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "doctor_id" => "required|numeric|exists:doctors,id",
                "balance" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = Doctor::find($request->doctor_id);

            if (!$doctor)
                return $this->returnError('E001', trans("messages.Doctor not found"));

            $doctor->update(['balance' => $request->balance]);

            return $this->returnSuccessMessage(trans('messages.Balance updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}
