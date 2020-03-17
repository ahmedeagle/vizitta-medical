<?php

namespace App\Http\Controllers;

use App\Traits\CustomPagesTrait;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use Validator;

class CustomPagesController extends Controller
{
    use GlobalTrait, CustomPagesTrait;

    public function getProviderPages(){
        try{
            $pages = $this->getProviderCustomPages();
            if(count($pages) > 0)
                return $this->returnData('pages', $pages);

            return $this->returnError('E001', trans('messages.There is no pages found'));
        } catch (\Exception $ex){
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getUserPages(){
        try{
            $pages = $this->getUserCustomPages();
            if(count($pages) > 0)
                return $this->returnData('pages', $pages);

            return $this->returnError('E001', trans('messages.There is no pages found'));
        } catch (\Exception $ex){
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getProviderPage(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $page = $this->getProviderPageById($request->id);
            if($page != null)
                return $this->returnData('page', $page);

            return $this->returnError('E001', trans('messages.There is no page with this id'));
        } catch (\Exception $ex){
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getUserPage(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                "id" => "required|numeric",
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            $page = $this->getUserPageById($request->id);
            if($page != null)
                return $this->returnData('page', $page);

            return $this->returnError('E001', trans('messages.There is no page with this id'));
        } catch (\Exception $ex){
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


}
