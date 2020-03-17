<?php

namespace App\Traits\Dashboard;

use App\Models\Manager;
use App\Models\Mix;
use App\Models\Payment;
use App\Models\Provider;
use Illuminate\Support\Facades\Hash;
use Freshbitsweb\Laratables\Laratables;
use DB;

trait AdminTrait
{
    public function agreement()
    {
        return Mix::find(1);
    }

    public function updateAgree($request)
    {
        $agreement = $this->agreement();
        $agreement = $agreement->update([
            'agreement_ar' =>  $request -> agreement_ar,
            'agreement_en' =>  $request -> agreement_en,
            'reservation_rules_ar' =>  $request -> reservation_rules_ar,
            'reservation_rules_en' =>  $request -> reservation_rules_en,
            'provider_reg_rules_ar' =>  $request ->provider_reg_rules_ar,
            'provider_reg_rules_en' =>  $request -> provider_reg_rules_en,
        ]);

         return $agreement;
    }

    public function getAppData()
    {
        return Manager::find(1)->makeVisible(['balance', 'unpaid_balance', 'paid_balance']);
    }

    public function getCoupBalances()
    {
        return Laratables::recordsOf(Payment::class);
    }

    public function updateInfo($request)
    {
        $manager = $this->getAppData();
        $result = [
            'email' => $request->email,
            'mobile' => $request->mobile,
        ];

        if (isset($request->password))
            $result['password'] =  $request->password ;

        $manager = $manager->update($result);
        return $manager;
    }

    public function getProvidersBalances()
    {  // main providers balances
        return Laratables::recordsOf(Provider::class, function ($query) {
            return $query->Has('providers')->select('*');
        });
    }

    public function getBranchesBalanceByProviderID($providerId)
    {
        return Laratables::recordsOf(Provider::class, function ($query) use ($providerId) {
            return $query->where('provider_id', $providerId);
        });
    }

}
