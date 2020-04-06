<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceTime;
use App\Models\Provider;
use App\Traits\CPanel\ServicesTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalTrait;
use Validator;
use DB;

class ServiceController extends Controller
{
    use GlobalTrait, ServicesTrait, OdooTrait, SMSTrait;

    public
    function index(Request $request)
    {
        $services = $this->getServices();
        if (count($services) > 0) {
            foreach ($services as $key => $service) {
                $service->time = "";
                $days = $service->times;
            }
            $total_count = $services->total();
            $per_page = PAGINATION_COUNT;
            $services->getCollection()->each(function ($service) {
                $service->makeHidden(['available_time', 'provider_id', 'branch_id']);
                return $service;
            });

            $services = json_decode($services->toJson());
            $servicesJson = new \stdClass();
            $servicesJson->current_page = $services->current_page;
            $servicesJson->total_pages = $services->last_page;
            $servicesJson->total_count = $total_count;
            $servicesJson->per_page = $per_page;
            $servicesJson->data = $services->data;

            return $this->returnData('services', $servicesJson);
        }

        return $this->returnError('D000', __('messages.no services'));
    }


}
