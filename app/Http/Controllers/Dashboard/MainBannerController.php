<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Banner;
use App\Models\Mbanner;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\PromoCode;
use App\Models\PromoCodeCategory;
use App\Traits\Dashboard\OfferCategoriesTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Flashy;

class MainBannerController extends Controller
{
    use  PublicTrait;

    public function index()
    {
        $data = [];
        $data['banners'] = Mbanner::select('id', 'photo', 'bannerable_type', 'bannerable_id')->orderBy('id', 'DESC')->paginate(10);
        return view('mainbanners.index', $data);
    }

    public function create()
    {
        $data = [];
        $data['categories'] = $this->getAllCategoriesCollection();
        $data['offers'] = $this->getAllOffersCollection();
        return view('mainbanners.create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "type" => "required|in:App\Models\PromoCodeCategory,App\Models\PromoCode,none",
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

        if ($request->type == 'App\Models\PromoCodeCategory') {
            $id = $request->category_id;
            $category = PromoCodeCategory::find($id);
            if (!$category && $id != 0) {  //0 means all categories screen
                Flashy::error('القسم المختار غير موجود لدينا');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

        } elseif ($request->type == 'App\Models\PromoCode') {
            $id = $request->offer_id;
            $offer = PromoCode::find($id);
            if (!$offer) {
                Flashy::error('العرض المختار غير موجود لدينا');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
        } else {
            $id = null;
        }

        Mbanner::create([
            'photo' => $fileName,
            'bannerable_type' => $request->type,
            'bannerable_id' => $id,
        ]);

        Flashy::success('تم إضافة البانر  بنجاح');
        return redirect()->route('admin.offers.mainbanners');
    }


    public function destroy($id)
    {
        try {
            $banner = Mbanner::find($id);
            if ($banner == null)
                return view('errors.404');
            $banner->delete();
            Flashy::success('تم مسح البنر بنجاح');
            return redirect()->route('admin.offers.mainbanners');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }
}
