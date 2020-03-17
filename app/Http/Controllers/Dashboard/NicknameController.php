<?php

namespace App\Http\Controllers\Dashboard;

use App\Traits\Dashboard\NicknameTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class NicknameController extends Controller
{
    use NicknameTrait;

    public function getDataTable(){
        try{
            return $this->getAllNicknames();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index(){
        return view('nickname.index');
    }

    public function add(){
        return view('nickname.add');
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $this->createNickname($request);
            Flashy::success('تم إضافة اللقب بنجاح');
            return redirect()->route('admin.nickname');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function edit($id){
        try{
            $nickname = $this->getNicknameById($id);
            if($nickname == null)
                return view('errors.404');

            return view('nickname.edit', compact('nickname'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function update($id, Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            $nickname = $this->getNicknameById($id);
            if($nickname == null)
                return view('errors.404');

            $this->updateNickname($nickname, $request);
            Flashy::success('تم تعديل اللقب بنجاح');
            return redirect()->route('admin.nickname');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id){
        try{
            $nickname = $this->getNicknameById($id);
            if($nickname == null)
                return view('errors.404');

            if(count($nickname->doctors) == 0){
                $nickname->delete();
                Flashy::success('تم مسح اللقب بنجاح');
            } else {
                Flashy::error('لا يمكن مسح لقب مرتبط بدكاترة');
            }
            return redirect()->route('admin.nickname');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}
