<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Manager;
use App\Models\Provider;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\Dashboard\UserTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Flashy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Validator;

class UserController extends Controller
{
    use UserTrait;

    public function getDataTable()
    {
        //laratables//
        try {
            $queryStr = request('queryStr');
            return $this->getAllUsers($queryStr);
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index()
    {

        // not lara tables
         $data = [];
        $queryStr = '';
        if (request('queryStr')) {
            $queryStr = request('queryStr');
        }
        $status = 'all';
        $list = ['all', 'no_reservations', 'android', 'ios',];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $data['users'] = $this->getUserByStatus();
            } else {
                $status = request('status') ? request('status') : $status;
                $data['users'] = $this->getUserByStatus($status);
            }
            return view('user.index', $data);
        }
        elseif (request('queryStr')) {  //search only by name
            $data['users'] = User::where('name', 'LIKE', '%' . trim($queryStr) . '%')->orderBy('id','DESC')->paginate(10);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $data['users'] = User::where('name', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('mobile', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('id_number', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('birth_date', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhereHas('city', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('insuranceCompany', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('point', function ($query) use ($q) {
                    $query->where('points', '=', trim($q));
                })
                ->orderBy('id','DESC')
                ->paginate(10);
        } else
            $data['users'] = User::orderBy('id','DESC')->paginate(10);

        return view('user.index', $data);
    }

    protected function getUserByStatus($status = 'all')
    {
        if ($status == 'no_reservations') {
            return $users = User::whereDoesntHave('reservations')->orderBy('id', 'DESC')->paginate(10);
        } else
            return $users = User::orderBy('id', 'DESC')->paginate(10);
    }

    public function view($id)
    {
        try {
            $user = $this->getUserById($id);
            if ($user == null)
                return view('errors.404');

            $reservations = $this->getUserReservations($user->id);
            $records = $this->getUserRecords($user->id);
            $favoriteDoctors = $this->getUserFavoriteDoctors($user->id);
            $favoriteProviders = $this->getUserFavoriteProviders($user->id);
            return view('user.view', compact('user', 'reservations', 'records', 'favoriteDoctors', 'favoriteProviders'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->getUserById($id);
            if ($user == null)
                return view('errors.404');

            if (count($user->reservations) > 0) {
                Flashy::error('لا يمكن مسح مستخدم لديه حجوزات');
            } else {
                $user->delete();
                Flashy::success('تم مسح المستخدم بنجاح');
            }
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function viewdestroy($id)
    {
        try {
            $user = $this->getUserById($id);
            if ($user == null)
                return view('errors.404');

            if (count($user->reservations) > 0) {
                Flashy::error('لا يمكن مسح مستخدم لديه حجوزات');
            } else {
                $user->delete();
                Flashy::success('تم مسح المستخدم بنجاح');
            }
            return view('user.index');
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function changeStatus($id, $status)
    {
        try {
            $user = $this->getUserById($id);
            if ($user == null)
                return view('errors.404');

            if ($status != 0 && $status != 1) {
                Flashy::error('إدخل كود التفعيل صحيح');
            } else {
                $this->changerUserStatus($user, $status);
                Flashy::success('تم تغيير حالة المستخدم بنجاح');
            }
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function getAdminsDataTable()
    {
        return $this->getAllAdmins();
    }

    public function showAdmins()
    {
        return view('admins.index');
    }

    public function destroyAdmin($id)
    {
        try {
            if (Auth::guard('web')->user()->id == $id) {
                Flashy::error('لا يمكن حذف عضويتك ');
                return redirect()->back();
            }
            $admin = Manager::findOrFail($id);
            $admin->delete();
            Flashy::success('تم حذف المستخدم  بنجاح');
            return redirect()->back();
        } catch (Exception $ex) {
            return abort('404');
        }

    }

    public function viewAdmin($id)
    {

    }

    public function editAdmin($id)
    {
        try {
            $admin = $this->getAdminById($id);
            if ($admin == null)
                return view('errors.404');

            return view('admins.edit', compact('admin'));
        } catch (Exception $ex) {
            return abort('404');
        }
    }

    public function addAdmin()
    {
        try {
            $permissions = Permission::get();
            return view('admins.create', compact('permissions'));
        } catch (Exception $ex) {
            return abort('404');
        }
    }


    public function storeAdmin(Request $request)
    {
        try {
            app()['cache']->forget('spatie.permission.cache');
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "password" => "required|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    /*  "digits_between:8,10",
                      "regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/"*/
                ),
                "email" => "sometimes|email"
            ]);

            if ($validator->fails()) {
                Flashy::error('هناك بعض الاخطاء  الرجاء اصلاحها ');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            DB::beginTransaction();

            try {

                $manager = Manager::create([
                    'name_en' => trim($request->name_en),
                    'name_ar' => trim($request->name_ar),
                    'password' => $request->password,
                    'mobile' => $request->mobile,
                    'email' => $request->email,
                ]);

                $permissions = $request->except(['name_ar', 'name_en', 'email', 'password', '_token', '_method', 'mobile']);
                $permissions_name = array_keys($permissions);
                if (!empty($permissions_name)) {
                    Manager::find($manager->id)->givePermissionTo($permissions_name);
                }
                DB::commit();
                Flashy::success('تم إضافة المستخدم  بنجاح  ');
                return redirect()->route('admin.admins');
            } catch (\Exception $ex) {
                DB::rollback();
                Flashy::error('فشلت عمليه الحفظ ');
                return redirect()->route('admin.admins');
            }
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function updateAdmin($id, Request $request)
    {
        try {
            app()['cache']->forget('spatie.permission.cache');

            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "password" => "sometimes|max:255",
                "mobile" => array(
                    "required",
                    "numeric",
                    //  "digits_between:8,10",
                    //"regex:/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/",
                ),
                "email" => "sometimes|email"
            ]);

            if ($validator->fails()) {
                Flashy::error('هناك بعض الاخطاء  الرجاء اصلاحها ');
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }

            DB::beginTransaction();

            try {

                $manager = Manager::find($id);
                $manager->update([
                    'name_en' => trim($request->name_en),
                    'name_ar' => trim($request->name_ar),
                    'mobile' => $request->mobile,
                    'email' => $request->email,
                ]);

                if ($request->has('password')) {
                    $manager->update([
                        'password' => $request->password
                    ]);
                }
                $permissions = $request->except(['name_ar', 'name_en', 'email', 'password', '_token', '_method', 'mobile']);
                $permissions_name = array_keys($permissions);

                //remove all current permissions
                DB::table('model_has_permissions')->where('model_id', $id)->delete();
                if (!empty($permissions_name)) {
                    $manager->givePermissionTo($permissions_name);
                }

                DB::commit();
                Flashy::success('تم تعديل المستخدم  بنجاح  ');
                return redirect()->route('admin.admins');
            } catch (\Exception $ex) {
                DB::rollback();
                Flashy::error('فشلت عمليه  التحديث  ');
                return redirect()->route('admin.admins');
            }
        } catch (\Exception $ex) {
            return view('errors.404');
        }


    }


    public function changeAdminStatus($id, $status)
    {
    }
}



