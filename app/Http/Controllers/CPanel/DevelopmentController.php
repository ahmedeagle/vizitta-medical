<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Mix;
use App\Models\Payment;
use App\Models\Provider;
use App\Traits\Dashboard\AdminTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DevelopmentController extends Controller
{
    use AdminTrait, PublicTrait, GeneralTrait;

    public function index()
    {
        try {
            $settings = Mix::first();
            return response()->json(['status' => true, 'data' => $settings]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $settings = Mix::first();
            if (!$settings) {
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }

            $settings->update($request->except('dev_company_logo'));

            $fileName1 = $settings->dev_company_logo;
            if (isset($request->dev_company_logo) && !empty($request->dev_company_logo)) {
                $fileName1 = $this->saveImage('generals', $request->dev_company_logo);
            }

            $settings->update([
                'dev_company_logo' => $fileName1,
            ]);

            return response()->json(['status' => true, 'msg' => __('main.settings_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
