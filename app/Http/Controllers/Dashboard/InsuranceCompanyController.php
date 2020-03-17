<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\InsuranceCompanyTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class InsuranceCompanyController extends Controller
{
    use InsuranceCompanyTrait, PublicTrait;

    public function getDataTable(){
        try{
            return $this->getAllCompanies();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('insurance-company.index');
    }

    public function add(){
        return view('insurance-company.add');
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "status" => "required|boolean",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $path = "";
            if(isset($request->image)){
                $path = $this->uploadImage('insurance', $request->image, 'insurance');
            }
            $company = $this->createInsuranceCompany($request);
            $company->update([
                'image' => $path
            ]);
            Flashy::success('تم إضافة شركة التأمين بنجاح');
            return redirect()->route('admin.insurance.company');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $company = $this->getCompanyById($id);
            if($company == null)
                return view('errors.404');

            return view('insurance-company.edit', compact('company'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "status" => "required|boolean",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $company = $this->getCompanyById($id);
            if($company == null)
                return view('errors.404');

            $path = $company->image;
            if(isset($request->image)){
                $path = $this->uploadImage('insurance', $request->image, 'insurance');
            }
            $this->updateInsuranceCompany($company, $request);
            $company->update([
                'image' => $path
            ]);
            Flashy::success('تم تعديل شركة التأمين بنجاح');
            return redirect()->route('admin.insurance.company');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $company = $this->getCompanyById($id);
            if($company == null)
                return view('errors.404');

            if(count($company->doctors) == 0){
                $company->delete();
                Flashy::success('تم مسح شركة التأمين بنجاح');
            } else {
                Flashy::error('لا يمكن مسح شركة تأمين مرتبطة بدكاترة');
            }
            return redirect()->route('admin.insurance.company');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function changeStatus($id, $status){
        try{
            $company = $this->getCompanyById($id);
            if($company == null)
                return view('errors.404');

            if($status != 0 && $status != 1){
                Flashy::error('إدخل كود التفعيل صحيح');
            } else {
                $this->changeCompanyStatus($company, $status);
                Flashy::success('تم تغيير حالة شركة التأمين بنجاح');
            }
            return redirect()->route('admin.insurance.company');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
