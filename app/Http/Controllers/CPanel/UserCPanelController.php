<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\UserResource;
use App\Models\Manager;
use App\Models\User;
use App\Traits\Dashboard\UserTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Spatie\Permission\Models\Permission;

class UserCPanelController extends Controller
{
    use UserTrait, GeneralTrait;

    public function index()
    {
        $result = Manager::select('id', 'name_en', 'name_ar', 'mobile', 'email', 'created_at')->paginate(PAGINATION_COUNT);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function create(Request $request)
    {
        try {
//            $permissions = Permission::get();
            $result['permissions_list'] = $this->getAdminPermissionsList();
            return response()->json(['status' => true, 'data' => $result]);
        } catch (Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $admin = $this->getAdminById($request->id);
            if ($admin == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $admin['permissions_list'] = $this->getAdminPermissionsList();
            $admin['admin_permissions'] = $admin->permissions;
            unset($admin['permissions']);

            return response()->json(['status' => true, 'data' => $admin]);
        } catch (Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            if (Auth::guard('manager-api')->user()->id == $request->id) {
                return response()->json(['success' => false, 'error' => __('main.can_not_delete_your_account')], 200);
            }
            $admin = Manager::findOrFail($request->id);
            $admin->delete();
            return response()->json(['status' => true, 'msg' => __('main.user_deleted_successfully')]);
        } catch (Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }

    public function store(Request $request)
    {
        try {
//            app()['cache']->forget('spatie.permission.cache');
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
                "email" => "sometimes|email",
                "permissions" => "required|array|min:0",
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
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

                /*$permissions = $request->except(['name_ar', 'name_en', 'email', 'password', '_token', '_method', 'mobile']);
                $permissions_name = array_keys($permissions);
                if (!empty($permissions_name)) {
                    Manager::find($manager->id)->givePermissionTo($permissions_name);
                }*/

                $permissions = $request->permissions;
                foreach ($permissions as $k => $permission) {
                    $manager->permissions()->attach($permission['permission_id'], [
                        'view' => $permission['view'],
                        'add' => $permission['add'],
                        'edit' => $permission['edit'],
                        'delete' => $permission['delete'],
                    ]);
                }

                DB::commit();
                return response()->json(['status' => true, 'msg' => __('main.user_added_successfully')]);
            } catch (\Exception $ex) {
                DB::rollback();
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
//            app()['cache']->forget('spatie.permission.cache');

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
                "email" => "sometimes|email",
                "permissions" => "required|array|min:0",
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            DB::beginTransaction();

            try {

                $manager = Manager::find($request->id);
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

                /*$permissions = $request->except(['name_ar', 'name_en', 'email', 'password', '_token', '_method', 'mobile']);
                $permissions_name = array_keys($permissions);

                //remove all current permissions
                DB::table('model_has_permissions')->where('model_id', $request->id)->delete();
                if (!empty($permissions_name)) {
                    $manager->givePermissionTo($permissions_name);
                }*/


                $permissions = $request->permissions;
                $manager->permissions()->detach();
                foreach ($permissions as $k => $permission) {
                    $manager->permissions()->attach($permission['permission_id'], [
                        'view' => $permission['view'],
                        'add' => $permission['add'],
                        'edit' => $permission['edit'],
                        'delete' => $permission['delete'],
                    ]);
                }

                DB::commit();
                return response()->json(['status' => true, 'msg' => __('main.user_updated_successfully')]);
            } catch (\Exception $ex) {
                DB::rollback();
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }


    }

}



