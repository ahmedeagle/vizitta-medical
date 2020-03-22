<?php

namespace App\Http\Controllers;

use App\Models\MedicalProfile;
use App\Models\MedicalProfileDiseases;
use App\Models\MedicalProfilePharmaceutical;
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
                "diseases" => "array",
                "pharmaceuticals" => "array",
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


            //////////////////save sensitivities for medical profile /////////////////////////
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
            //////////////////end sensitivities for medical profile /////////////////////////


            //////////////////save diseases for medical profile /////////////////////////
            if (isset($request->diseases) && is_array($request->diseases)) {
                $data = [];
                $diseases = array_filter($request->diseases, function ($value) {
                    return !is_null($value) && $value !== '';
                });

                foreach ($diseases as $disease) {
                    $data[] = ['medical_profile_id' => $medical_profile->id, 'diseases_name' => $disease];
                }
                $medical_profile->disease()->delete();
                $medical_profile->disease()->insert($data);

            } else
                $medical_profile->disease()->delete();
            //////////////////end diseases for medical profile /////////////////////////

            //////////////////save pharmaceuticals for medical profile /////////////////////////
            if (isset($request->pharmaceuticals) && is_array($request->pharmaceuticals)) {
                $data = [];
                $pharmaceuticals = array_filter($request->pharmaceuticals, function ($value) {
                    return !is_null($value) && $value !== '';
                });

                foreach ($pharmaceuticals as $pharmaceutical) {
                    $data[] = ['medical_profile_id' => $medical_profile->id, 'pharmaceutical_name' => $pharmaceutical];
                }
                $medical_profile->pharmaceuticals()->delete();
                $medical_profile->pharmaceuticals()->insert($data);

            } else
                $medical_profile->pharmaceuticals()->delete();
            //////////////////end pharmaceuticals for medical profile /////////////////////////

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
                $medical_profile->pharmaceuticals = MedicalProfilePharmaceutical::where('medical_profile_id', $medical_profile->id)->pluck('pharmaceutical_name as name');
                $medical_profile->diseases = MedicalProfileDiseases::where('medical_profile_id', $medical_profile->id)->pluck('diseases_name as name');
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
