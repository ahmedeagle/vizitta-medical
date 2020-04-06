<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Banner;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Traits\CPanel\BannerTrait;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class BannerController extends Controller
{
    use  GlobalTrait, BannerTrait;

    public function index()
    {
        try {
            $banners = $this->getBannersV2();
            if (count($banners->toArray()) > 0) {
                $banners->each(function ($banner) {
                    $banner->type = $banner->type === 'App\Models\OfferCategory' ? 'category' : 'offer';
                    return $banner;
                });
            }
            return $this->returnData('banners', $banners);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function create()
    {
        try {
            $categories = $this->getAllCategories();
            return $this->returnData('categories', $categories);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "type" => "required|in:App\Models\OfferCategory,App\Models\Offer",
            "offer_id" => "required",
            "category_id" => "required",
            "photo" => "required|mimes:jpeg,jpg,png"
        ]);


        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }

        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->uploadImage('copouns', $request->photo);
        }

        if ($request->type == 'App\Models\OfferCategory') {
            $id = $request->category_id;
            $category = OfferCategory::find($id);
            if (!$category && $id != 0) {  //0 means all categories screen
                Flashy::error('القسم المختار غير موجود لدينا');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

        } else {
            $id = $request->offer_id;
            $offer = Offer::find($id);
            if (!$offer) {
                Flashy::error('العرض المختار غير موجود لدينا');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
        }

        Banner::create([
            'photo' => $fileName,
            'bannerable_type' => $request->type,
            'bannerable_id' => $id,
            'subCategory_id' => $request->subcategory_id
        ]);

        Flashy::success('تم إضافة البانر  بنجاح');
        return redirect()->route('admin.offers.banners');
    }


    public function destroy($id)
    {
        try {
            $banner = Banner::find($id);
            if ($banner == null)
                return view('errors.404');
            $banner->delete();
            Flashy::success('تم مسح البنر بنجاح');
            return redirect()->route('admin.offers.banners');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }
}
