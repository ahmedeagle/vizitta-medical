<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\CustomPageTrait;
use Validator;
use Flashy;

class CustomPageController extends Controller
{
    use CustomPageTrait;

    public function getDataTable(){
        try{
            return $this->getAll();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('customPage.index');
    }

    public function add(){
        return view('customPage.add');
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "title_en" => "required|max:255",
                "title_ar" => "required|max:255",
                "content_en" => "required",
                "content_ar" => "required",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createCustomPage($request);
            Flashy::success('تم إضافة الصفحة بنجاح');
            return redirect()->route('admin.customPage');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $customPage = $this->getCustomPageById($id);
            if($customPage == null)
                return view('errors.404');

            return view('customPage.edit', compact('customPage'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "title_en" => "required|max:255",
                "title_ar" => "required|max:255",
                "content_en" => "required",
                "content_ar" => "required",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $customPage = $this->getCustomPageById($id);
            if($customPage == null)
                return view('errors.404');

            $this->updateCustomPage($customPage, $request);
            Flashy::success('تم تعديل الصفحة بنجاح');
            return redirect()->route('admin.customPage');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $customPage = $this->getCustomPageById($id);
            if($customPage == null)
                return view('errors.404');

            $customPage->delete();
            Flashy::success('تم مسح الصفحة بنجاح');
            return redirect()->route('admin.customPage');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function changeStatus($id, $status){
        try{
            $customPage = $this->getCustomPageById($id);
            if($customPage == null)
                return view('errors.404');

            if($status != 0 && $status != 1){
                Flashy::error('إدخل الحالة صحيحه');
            } else {
                $this->changerCustomPageStatus($customPage, $status);
                Flashy::success('تم تغيير حالة الصفحة بنجاح');
            }
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
