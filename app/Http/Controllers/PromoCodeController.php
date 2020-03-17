<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\PromoCodeTrait;
use Illuminate\Http\Request;
use Validator;

class PromoCodeController extends Controller
{
    use GlobalTrait, DoctorTrait, PromoCodeTrait;

    public function checkPromoCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "promocode" => "required|max:255",
                "doctor_id" => "required|numeric"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $doctor = Doctor::with('times')->find($request->doctor_id);
            $specification = $doctor->specification_id;
            $promoCode = $this->getPromoByCode($request->promocode, $request->doctor_id, $doctor->provider_id); // discount coupon
            if ($promoCode) {
                $promo_id = $promoCode->id;
                if ($promoCode->available_count > 0) {
                    $promoCode->update([
                        'available_count' => ($promoCode->available_count - 1)
                    ]);

                    $promoCode = [
                        "discount" => $promoCode->discount,
                        "price_after_discount"  =>  $promoCode-> price_after_discount,
                        "price"   =>   $promoCode-> price
                    ];
                    return $this->returnData('promocode', json_decode(json_encode($promoCode, JSON_FORCE_OBJECT)));
                } else {
                    return $this->returnError('E002', trans('messages.PromoCode is not applicable to your booking'));
                }
            }

            return $this->returnError('E002', trans('messages.PromoCode is not applicable to your booking'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }




}
