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
}
