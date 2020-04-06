<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Banner;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\PromoCode;
use App\Models\PromoCodeCategory;
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
                    if ($banner->type == 'App\Models\OfferCategory') {
                        $type = 'category';
                        if ($banner->type_id == 0) {
                            $direct_type = 'أقسام';
                            $direct_to = 'كل الاقسام';
                        } else {
                            $category = OfferCategory::whereNull('parent_id')->where('id', $banner->type_id)->first();
                            $direct_type = 'أقسام';
                            $direct_to = @$category->{'name_' . app()->getLocale()};
                        }
                    } elseif ($banner->type == 'App\Models\Offer') {
                        $type = 'offer';
                        $direct_type = 'عروض';
                        $offer = Offer::find($banner->type_id);
                        $direct_to = @$offer->{'title_' . app()->getLocale()};
                    } else {
                        $type = 'none';
                        $direct_type = 'لا شي';
                        $direct_to = 'لا شي';
                    }
                    $banner->type = $type;
                    $banner->direct_type = $direct_type;
                    $banner->direct_to = $direct_to;

                    unset($banner->type_id);
                    unset($banner->subCategory_id);
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
            "type" => "required|in:offer,category,none",
            "photo" => "required|mimes:jpeg,jpg,png"

        ]);

        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }


        if ($request->type == 'category') {

            // 0 -> means all category of offers    otherwise mean offer category id
            if (empty($request->category_id) or !is_numeric($request->category_id)) {
                return $this->returnError('D000', __('messages.category required'));
            }

            //check if main category not equal 0 (i.e not all categroy)  we must check if this main category exists or not
            if ($request->category_id != 0) {
                $category = OfferCategory::whereNull('parent_id')->where('id', $request->category_id)->first();
                if (!$category) {
                    return $this->returnError('D000', __('messages.category not found'));
                }
                // required only if category_id  not equal 0  //i.e not all categories then we need subcategory of this category
                if (empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) {
                    return $this->returnError('D000', __('messages.subcategory required'));
                }
            }

            //check if subcategory exists
            if ($request->has('subcategory_id')) {
                if ($request->subcategory_id != 0) {
                    $subCategory = OfferCategory::whereNotNull('parent_id')->where('id', $request->category_id)->first();
                    if (!$subCategory) {
                        return $this->returnError('D000', __('messages.subcategory not found'));
                    }
                }
            }

        }

        if ($request->type == 'offer') {
            if (empty($request->offer_id) or !is_numeric($request->offer_id)) {
                return $this->returnError('D000', __('messages.offer required'));
            }
            $offer = Offer::where('id', $request->offer_id);   // offer subcategory
            if (!$offer)
                return $this->returnError('D000', __('messages.offer not found'));
        }

        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->saveImage('copouns', $request->photo);
        }

        if ($request->type == 'category') {
            $id = $request->category_id;
            $bannerable_type = 'App\Models\OfferCategory';
        } elseif ($request->type == 'offer') {
            $id = $request->offer_id;
            $bannerable_type = 'App\Models\Offer';
        } else {
            $id = null;
            $bannerable_type = 'none';
        }

        Banner::create([
            'photo' => $fileName,
            'bannerable_type' => $bannerable_type,
            'bannerable_id' => $id,
            'subCategory_id' => isset($request->subcategory_id) ? $request->subcategory_id : 0
        ]);

        return $this->returnSuccessMessage(trans('messages.Banner added successfully'));
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
