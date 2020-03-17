<?php

namespace App\Traits\Dashboard;

use App\Models\InsuranceCompany;
use Freshbitsweb\Laratables\Laratables;

trait InsuranceCompanyTrait
{
    public function getCompanyById($id){
        return InsuranceCompany::find($id);
    }

    public function getAllCompanies(){
        return Laratables::recordsOf(InsuranceCompany::class);
    }

    public function createInsuranceCompany($request){
        $company = InsuranceCompany::create($request->all());
        return $company;
    }

    public function updateInsuranceCompany($company, $request){
        $company = $company->update($request->all());
        return $company;
    }

    public function changeCompanyStatus($company, $status){
        $company = $company->update([
            'status' => $status
        ]);
        return $company;
    }
}