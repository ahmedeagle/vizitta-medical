<?php

namespace App\Http\Controllers;

use App\Models\MedicalProfile;
use App\Models\MedicalProfileSensitivity;
use App\Models\Sensitivity;
use Illuminate\Http\Request;
use App\Traits\GlobalTrait;
use Validator;
use DB;

class MedicalProfileController extends Controller
{
    use GlobalTrait;

    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "blood" => "required|string|max:191",
                "sensitivities" => "array",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $validation = $this->validateFields(['user_id' => $request->user_id]);

            DB::beginTransaction();

            if (!$validation->user_found)
                return $this->returnError('D000', trans("messages.There is no user with this id"));


            $medical_profile = MedicalProfile::where('user_id', $request->user_id)->first();
            if (!$medical_profile)
                $medical_profile = MedicalProfile::create(['blood' => $request->blood, 'user_id' => $request->user_id]);
            else
                $medical_profile->update(['blood' => $request->blood, 'user_id' => $request->user_id]);

            if (isset($request->sensitivities) && is_array($request->sensitivities)) {
                $data = [];

                $sensitivities = array_filter($request->sensitivities, function ($value) {
                    return !is_null($value) && $value !== '';
                });

                foreach ($sensitivities as $sensitivity) {
                    $data[] = ['medical_profile_id' => $medical_profile->id, 'sensitivity_id' => $sensitivity];
                }
                $medical_profile->sensitivities()->delete();
                $medical_profile->sensitivities()->insert($data);

            } else
                $medical_profile->sensitivities()->delete();

            DB::commit();
            return $this->returnSuccessMessage(trans('messages.Medical profile updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function show(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $medical_profile = MedicalProfile::with(['user' => function ($q) {
                $q->select('id', 'users.id', 'users.name');
            }])->where('user_id', $request->user_id)->first();

            if ($medical_profile != null) {
                $medical_profile->sensitivities = MedicalProfileSensitivity::where('medical_profile_id', $medical_profile->id)->pluck('sensitivity_id as name');
                return $this->returnData('MedicalProfile', json_decode($medical_profile, true));
            }
            return $this->returnError('E001', trans('messages.No medical profile with this user id'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    /*public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "user_id" => "required|numeric",
                "blood" => "required|string|max:191",
                "sensitivities" => "required|array",
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $validation = $this->validateFields(['sensitivities'=>$request->sensitivities]);

            $medical_profile = MedicalProfile::where('user_id', $request->user_id)->first();
            if($medical_profile)
                return $this->returnError('E001', trans('messages.No medical profile with this user id'));

            DB::beginTransaction();

            if(!$validation->sensitivities_found)
                return $this->returnError('D000', trans("messages.There is one incorrect sensitivity id"));

            $medical_profile->update(['blood'=>$request->blood]);

            $data = [];
            foreach($request->sensitivities as $sensitivity){
                $data[] = ['medical_profile_id'=>$medical_profile->id,'sensitivity_id'=>$sensitivity];
            }

            $medical_profile->sensitivities()->delete();
            $medical_profile->sensitivities()->save($data);

           DB::commit();
            return $this->returnSuccessMessage(trans('messages.Medical profile updated successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }*/

    public function showSensitivities()
    {
        try {

            $sensitivities = Sensitivity::select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'))->get();

            if ($sensitivities) {
                return $this->returnData('sensitivities', $sensitivities);
            }
            return $this->returnError('E001', trans('messages.No Sensitivities available right now'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}
