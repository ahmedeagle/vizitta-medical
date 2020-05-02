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

class SettingsController extends Controller
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
//            dd($request->all());
            $settings = Mix::first();
            if (!$settings) {
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }

            $settings->update($request->except('home_image1', 'home_image2', 'consulting_photo'));

            $fileName1 = $settings->home_image1;
            $fileName2 = $settings->home_image2;
            $fileName3 = $settings->consulting_photo;

            if (isset($request->home_image1) && !empty($request->home_image1)) {
                $fileName1 = $this->saveImage('generals', $request->home_image1);
            }

            if (isset($request->home_image2) && !empty($request->home_image2)) {
                $fileName2 = $this->saveImage('generals', $request->home_image2);
            }

            if (isset($request->consulting_photo) && !empty($request->consulting_photo)) {
                $fileName3 = $this->saveImage('generals', $request->consulting_photo);
            }

            $settings->update([
                'home_image1' => $fileName1,
                'home_image2' => $fileName2,
                'consulting_photo' => $fileName3,
            ]);

//            $this->updateAgree($request);
            return response()->json(['status' => true, 'msg' => __('main.settings_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}
