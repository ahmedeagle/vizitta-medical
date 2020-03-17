<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\LotteryBranchesResource;
use App\Models\Gift;
use App\Models\User;
use App\Models\UserGift;
use App\Traits\Dashboard\LotteryTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\PromoCodeTrait;
use App\Models\Provider;
use App\Models\Doctor;
use App\Models\PromoCode;
use App\Models\PromoCode_branch;
use App\Models\PromoCode_Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LotteryController extends Controller
{
    use  PublicTrait, LotteryTrait;

    public function index()
    {
        $providers = Provider::withdrawable()->select('id', 'name_ar', 'name_en', 'provider_id')->paginate(PAGINATION_COUNT);
        return response()->json(['status' => true, 'data' => $providers]);
    }

    public function lotteryBranches()
    {
        $res = ['status' => true];
        $branches = Provider::whereNull('provider_id')->where('lottery', 1)->paginate(PAGINATION_COUNT);
        $res['data'] = new LotteryBranchesResource($branches);
        return response()->json($res);
    }

    public function loadBranchGifts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id",
                "amount" => "required|numeric|min:1"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $amount = $request->amount;
            $provider = Provider::whereHas('gifts', function ($q) use ($amount) {
                $q->where('amount', '>=', $amount);
            })->find($request->provider_id);

            if (!$provider) {
                return response()->json(['success' => false, 'error' => ['amount' => [__('main.sorry_the_required_number')]]], 200);
            }
            $gifts = $provider->gifts->where('amount', '>', 0);
            return response()->json(['status' => true, 'data' => $gifts]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function loadGiftUsers(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id",
                "gift_id" => "required|exists:gifts,id",
                "amount" => "required|numeric|min:1"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $amount = $request->amount;
            $provider = Provider::whereHas('gifts', function ($q) use ($amount) {
                $q->where('amount', '>=', $amount);
            })->find($request->provider_id);

            if (!$provider) {
                return response()->json(['success' => false, 'error' => ['amount' => [__('main.sorry_the_required_number')]]], 200);
            }

            $gift = Gift::find($request->gift_id);

            if ($gift->amount < $request->amount) {
                return response()->json(['success' => false, 'error' => ['amount' => [__('main.sorry_the_required_number_for_gift')]]], 200);
            }

            //get random  user  equal to amount
            $users = User::active()->whereDoesntHave('gifts')->orderBy(DB::raw('RAND()'))->limit($request->amount)->get(['id', 'name', 'mobile']);

            if (isset($users) && $users->count() > 0) {

                //decrease gift amount
                $amountAfterDrawing = $gift->amount - $request->amount;
                $gift->update(['amount' => $amountAfterDrawing]);

                foreach ($users as $user) {
                    UserGift::create([
                        'user_id' => $user->id,
                        'gift_id' => $request->gift_id
                    ]);
                }
            }

            return response()->json(['status' => true, 'data' => $users]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function showBranchGifts(Request $request)
    {
        $branch = Provider::find($request->branchId);
        if (!$branch) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }
        $result['branch_name'] = app()->getLocale() == 'ar' ? $branch->name_ar : $branch->name_en;
        $result['gifts'] = $branch->gifts;
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function addGiftToBranch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "amount" => "required|numeric|min:1",
                "provider_id" => "required|exists:providers,id",
                "title" => "required|max:200"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            Gift::create([
                'branch_id' => $request->provider_id,
                'amount' => $request->amount,
                'title' => $request->title,
            ]);

            return response()->json(['status' => true, 'msg' => __('main.gift_added_to_branch_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function deleteGiftTo(Request $request)
    {
        $gift = Gift::find($request->giftId);
        if (!$gift) {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }

        if (isset($gift->user) && $gift->user->count() > 0) {
            return response()->json(['success' => false, 'error' => __('main.sorry_can_not_delete_gift')], 200);
        }
        $gift->delete();
        return response()->json(['status' => true, 'msg' => __('main.gift_deleted_successfully')]);
    }
}
