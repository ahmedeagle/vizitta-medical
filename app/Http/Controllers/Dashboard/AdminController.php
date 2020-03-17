<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Payment;
use App\Models\Provider;
use App\Traits\Dashboard\AdminTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class AdminController extends Controller
{
    use AdminTrait, PublicTrait;

    public function getAgreement()
    {
        try {
            $agreement = $this->agreement();
            return view('admin.agreement', compact('agreement'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function editAgreement()
    {
        try {
            $agreement = $this->agreement();
            return view('admin.editAgreement', compact('agreement'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function updateAgreement(Request $request)
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
            Flashy::success('تم تعديل الإتفاقية وشروط الحجز بنجاح');
            return redirect()->route('admin.data.agreement');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function getProviders()
    {
        return $this->getProvidersBalances();
    }

    public function getbranches($providerId)
    {
        return $this->getBranchesBalanceByProviderID($providerId);
    }

    public function getInformation()
    {
        $appData = $this->getAppData();
         $debitBalance = Provider::whereNotNull('provider_id')->where(function ($q) {
            $q->where('balance', '>', 0);
        })->sum('balance');

         Provider::with(['payments' => function($q){
            $q->sum('provider_value_of_coupon');
        }]) -> get();

        $creditBalance = Provider::whereNotNull('provider_id')->where(function ($q) {
            $q->where('balance', '<', 0);
        })->sum('balance');

        $couponsBalance = Payment::where('paid',1) -> sum('provider_value_of_coupon');

        return view('admin.information', compact('appData', 'creditBalance', 'debitBalance','couponsBalance'));
    }

    public function getCouponsBalances()
    {
            return $this->getCoupBalances();
    }

    public function editInformation()
    {
        try {
            $appData = $this->getAppData();
            return view('admin.editInformation', compact('appData'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function showProviderBranchesBalance($providerId)
    {
        try {
            $provider = $this->getProvider($providerId);
            if (!$provider)
                return view('errors.404');

            return view('admin.showBranchesBalance', compact('provider'));

        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function updateInformation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "mobile" => "required",
                "email" => "required|email"
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->updateInfo($request);
            Flashy::success('تم تعديل البيانات بنجاح');
            return redirect()->route('admin.data.information');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function editProviderBalance($id)
    {
        try {
            $provider = $this->getProvider($id);
            if ($provider == null)
                return view('errors.404');
            if ($provider->provider_id == null) // main provider
            {
                Flashy::error('لأ يمكن تعديل رصيد مقدم الخدمة الرئيسي ');
                return redirect()->back();
            }

            return view('admin.editProviderBalance', compact('provider'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function updateProviderBalance($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "balance" => "required|numeric"
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $provider = $this->getProvider($id);
            if ($provider == null)
                return view('errors.404');

            if ($provider->provider_id == null) {
                Flashy::error('لا يمكن تعديل رصيد مقدم خدمة رئيسي ');
                return redirect()->back();
            }

            /* if ($request->paid_balance > $provider->unpaid_balance) {
                 Flashy::error('القيمة المحصلة يجب انت تكون اقل من او يساوى القيمة الغير مدفوعة من مقدم الخدمة');
                 return redirect()->back();
             }*/
            $provider->update([
                'balance' => ($request->balance),
            ]);

            Flashy::success('تم تعديل الرصيد بنجاح');
            return redirect()->route('admin.data.information');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
