<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Mix;
use App\Models\Payment;
use App\Models\Provider;
use App\Traits\Dashboard\AdminTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AgreementController extends Controller
{
    use AdminTrait, PublicTrait;

    public function index()
    {
        try {
            $agreement = Mix::find(1, ['agreement_ar', 'agreement_en', 'reservation_rules_ar', 'reservation_rules_en', 'provider_reg_rules_ar', 'provider_reg_rules_en']);
            return response()->json(['status' => true, 'data' => $agreement]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $agreement = Mix::find(1, ['agreement_ar', 'agreement_en', 'reservation_rules_ar', 'reservation_rules_en', 'provider_reg_rules_ar', 'provider_reg_rules_en']);
            return response()->json(['status' => true, 'data' => $agreement]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            /* $validator = Validator::make($request->all(), [
                 "agreement_ar" => "required",
                 "agreement_en" => "required",
                 "reservation_rules_en" => "required",
                 "reservation_rules_ar" => "required",
                 "provider_reg_rules_ar" => 'required',
                 "provider_reg_rules_en" => 'required',
             ]);
             if ($validator->fails()) {
                 return redirect()->back()->withErrors($validator)->withInput($request->all());
             }*/

            $this->updateAgree($request);
            return response()->json(['status' => true, 'msg' => __('main.agreement_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
