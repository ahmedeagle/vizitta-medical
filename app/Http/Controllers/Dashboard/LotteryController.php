<?php

namespace App\Http\Controllers\Dashboard;

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
use Flashy;
use Validator;
use DB;

class LotteryController extends Controller
{
    use  PublicTrait, LotteryTrait;

    public function getDataTable()
    {
        return $this->getLotteryBranches();

    }

    public function lotteryBranches()
    {
       // dd($this -> maskPhoneNumber('0512345678'));
        return view('lotteries.index');
    }

    public function addLotteryBranch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                return response()->json([], '422');
            }
            $provider = Provider::find($request->provider_id);
            if ($provider->lottery == 1) {
                return response()->json(['branchId' => $request->provider_id], '200');
            }
            $provider->update(['lottery' => 1]);
            return response()->json(['branchId' => $request->provider_id], '200');
        } catch (\Exception $ex) {
            return abort('404');
        }
    }

    public function removeLotteryBranch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id"
            ]);

            if ($validator->fails()) {
                return response()->json([], '422');
            }

            $provider = Provider::find($request->provider_id);
            if ($provider->lottery == 0) {
                return response()->json([], '422');
            }
            $provider->update(['lottery' => 0]);
            return response()->json(['branchId' => $request->provider_id], '200');
        } catch (\Exception $ex) {
            return abort('404');
        }
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
                Flashy::error($validator->errors()->first());
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            Gift::create([
                'branch_id' => $request->provider_id,
                'amount' => $request->amount,
                'title' => $request->title,
            ]);

            Flashy::success('تم أضافه الهديه للعياده  بنجاح');
            return redirect()->route('admin.lotteriesBranches.index');
        } catch (\Exception $ex) {
            return abort('404');
        }
    }

    public function showBranchGifts($branchId)
    {
        $branch = Provider::find($branchId);
        if (!$branch) {
            Flashy::error('العياده غير موجوده لدينا');
            return redirect()->back();
        }
        $gifts = $branch->gifts;
        return view('lotteries.gifts', compact('gifts'))->with('branch_name', $branch->name_ar);
    }

    public function deleteGiftTo($giftId)
    {
        $gift = Gift::find($giftId);
        if (!$gift) {
            Flashy::error(' الهدية غير موجوده ');
            return redirect()->back();
        }

        if (isset($gift->user) && $gift -> user->count() > 0) {
            Flashy::error(' لايمكن حذف الهدية حيث انها ترتبط بسحوبات مستخدمين حاليا');
            return redirect()->back();
        }
        $gift->delete();
        Flashy::success('تم حذف الهدية بنجاح ');
        return redirect()->back();
    }

    public function getDrawing()
    {
        $providers = Provider::withdrawable()->select('id', 'name_ar', 'provider_id')->get();
        return view('drawing', compact('providers'));
    }


    public function loadBranchGifts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "provider_id" => "required|exists:providers,id",
                "amount" => "required|numeric|min:1"
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
            $amount = $request->amount;
            $provider = Provider::whereHas('gifts', function ($q) use ($amount) {
                $q->where('amount', '>=', $amount);
            })->find($request->provider_id);

            if (!$provider) {
                return response()->json(['amount' => [' عذرا العدد المطلوب اكبر من الهدايا المتاحه للعياده المختاره ']], 422);
            }
            $gifts = $provider->gifts->where('amount', '>', 0);
            $view = view('lotteries.loadGiftsByBranch', compact('gifts'))->renderSections();
            return response()->json([
                'content' => $view['main'],
            ]);
        } catch (\Exception $ex) {
            return abort('404');
        }
    }

    public function loadGiftUsers(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "provider_id" => "required|exists:providers,id",
            "gift_id" => "required|exists:gifts,id",
            "amount" => "required|numeric|min:1"
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $amount = $request->amount;
        $provider = Provider::whereHas('gifts', function ($q) use ($amount) {
            $q->where('amount', '>=', $amount);
        })->find($request->provider_id);

        if (!$provider) {
            return response()->json(['amount' => [' عذرا العدد المطلوب اكبر من الهدايا المتاحه للعياده المختاره ']], 422);
        }

        $gift = Gift::find($request->gift_id);

        if ($gift->amount < $request->amount) {
            return response()->json(['amount' => [' عذرا العدد المطلوب اكبر من العدد المتاح للهدية المختاره  ']], 422);
        }

        //get random  user  equal to amount
        $users = User::active()->whereDoesntHave('gifts')->orderBy(DB::raw('RAND()'))->limit($request->amount)->get();

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

        $view = view('lotteries.loadGiftsusers', compact('users'))->renderSections();
        return response()->json([
            'content' => $view['main'],
        ]);

    }

    public function users()
    {
         $users = User::active()->whereHas('gifts')->get();
         $userNotWinUntillNow = User::active()->whereDoesntHave('gifts') -> count();
        return view('lotteries.users', compact('users')) -> with('userNotWinUntillNow',$userNotWinUntillNow);
    }

}
