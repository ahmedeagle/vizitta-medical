<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Mix;
use App\Models\Payment;
use App\Models\Provider;

use App\Models\Reason;
use App\Models\Subscribtion;
use App\Traits\Dashboard\PublicTrait;
use Freshbitsweb\Laratables\Laratables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class GeneralController extends Controller
{
    use  PublicTrait;

    public function getContents()
    {
        $settings = Mix::first();
        return view('settings.contents', compact('settings'));
    }

    public function getDevelopmentContents()
    {
        $settings = Mix::first();
        return view('development.contents', compact('settings'));
    }


    public function updateContents(Request $request)
    {
        $settings = Mix::first();
        if (!$settings) {
            Flashy::error(' حدث خطا ما رجاء المحاولة فيما بعد ');
            return redirect()->back()->withInput($request->all());
        }


        $validator = Validator::make($request->all(), [
            //"price_less" => "required|numeric",
            "point_price" => "required|numeric",
            "bank_fees" => "required|numeric",
        ]);

        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        $settings->update($request->except('home_image1', 'home_image2'));
        $fileName1 = $settings->home_image1;
        $fileName2 = $settings->home_image2;
        if (isset($request->home_image1) && !empty($request->home_image1)) {
            $fileName1 = $this->uploadImage('generals', $request->home_image1);
        }
        if (isset($request->home_image2) && !empty($request->home_image2)) {
            $fileName2 = $this->uploadImage('generals', $request->home_image2);
        }
        $settings->update([
            'home_image1' => $fileName1,
            'home_image2' => $fileName2,
        ]);
        Flashy::success(' تم تحديث البيانات بنجاح  ');
        return redirect()->back();
    }

    public function updateDevelopmentContents(Request $request)
    {
        $settings = Mix::first();
        if (!$settings) {
            Flashy::error(' حدث خطا ما رجاء المحاولة فيما بعد ');
            return redirect()->back()->withInput($request->all());
        }

        $settings->update($request->except('dev_company_logo'));

        $fileName1 = $settings->dev_company_logo;
        if (isset($request->dev_company_logo) && !empty($request->dev_company_logo)) {
            $fileName1 = $this->uploadImage('generals', $request->dev_company_logo);
        }

        $settings->update([
            'dev_company_logo' => $fileName1,
        ]);

        Flashy::success(' تم تحديث البيانات بنجاح  ');
        return redirect()->back();
    }

    public function getSubscriptions()
    {
        return view('subscribtions.index');
    }

    public function getSubscribtionsData()
    {
        return Laratables::recordsOf(Subscribtion::class);

    }

    public function deleteSubscription($id)
    {
        try {
            $subscribe = Subscribtion::find($id);
            if ($subscribe == null)
                return view('errors.404');

            $subscribe->delete();
            Flashy::success('تم  الحذف بنجاح');

            return redirect()->route('admin.subscriptions.index');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function getReasons()
    {
        return view('reasons.index');
    }

    public function getReasonsData()
    {
        return Laratables::recordsOf(Reason::class);
    }

    public function addReason()
    {
        return view('reasons.add');
    }

    public function storeReason(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name_en" => "required|max:255",
            "name_ar" => "required|max:255",
        ]);
        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }
        Reason::create($request->all());
        Flashy::success('تم إضافة  السبب  بنجاح');
        return redirect()->route('admin.reasons.index');

    }

    public function editReason($id)
    {
        try {
            $reason = Reason::find($id);
            if ($reason == null)
                return view('errors.404');

            return view('reasons.edit', compact('reason'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function updateReason($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $reason = Reason::find($id);
            if ($reason == null)
                return view('errors.404');

            $reason->update($request->all());
            Flashy::success('تم تعديل  السبب  بنجاح');
            return redirect()->route('admin.reasons.index');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroyReason($id)
    {
        try {
            $reason = Reason::find($id);
            if ($reason == null)
                return view('errors.404');

            $reason->delete();
            Flashy::success('تم مسح السبب بنجاح');

            return redirect()->route('admin.reasons.index');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function search(Request $request)
    {
        $queryStr = $request->queryStr;
        $type = $request->type_id;
        if ($type) {
            if ($type != 'provider' && $type != 'branch' && $type != 'doctor' && $type != 'users') {
                return redirect()->route('home');
            }
        } else {
            return redirect()->route('home');
        }
        $url = "mc33/{$type}/?queryStr=" . $queryStr;
        return redirect($url);
    }

    public function sharingSettings()
    {
        $settings = Mix::select('owner_points', 'invited_points')->first();
        return view('sharing.index', compact('settings'));
    }

    public function updateSharingSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "owner_points" => "sometimes|nullable|required_with:active_owner_points|numeric|min:0",
                "invited_points" => "sometimes|nullable|required_with:active_invited_points|numeric|min:0",
            ]);
            if ($validator->fails()) {
                Flashy::error($validator->errors()->first());
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            Mix::first()->update([
                'owner_points' => $request->has('active_owner_points') ? $request->owner_points : 0,
                'invited_points' => $request->has('active_invited_points') ? $request->invited_points : 0,
            ]);
            Flashy::success('تمت العملية بنجاح ');
            return redirect()->route('admin.sharing');
        } catch (\Exception $ex) {
            return $ex;
        }
    }
}
