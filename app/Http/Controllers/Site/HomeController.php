<?php


namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    use GlobalTrait;

    public function index()
    {
        return view('front.index');
    }

    public function getProvidersOnMap(Request $request)
    {
        $results = $this->searchResult(null, $request);
        if (count($results->toArray()) > 0) {
            $results->each(function ($result) use ($request) {
                $result->distance = (string)number_format($result->distance * 1.609344, 2);
                unset($result->favourites);
                return $result;
            });

        }

        // dd($results->toArray());
        return view('map.index', compact('results'));

    }

    public function searchResult($userId = null, Request $request = null)
    {
        $order = (isset($request->order) && strtolower($request->order) == "desc") ? "DESC" : "ASC";
        $query = Provider::query();

        $provider = $query
            ->select('id',
                'logo',
                'longitude',
                'latitude',
                'address',
                DB::raw('name_' . $this->getCurrentLang() . ' as name'),
                DB::raw("'0' as distance")
            );

        $provider = $provider->orderBy('distance', $order)->whereNotNull('providers.provider_id')->get();
        return $provider->each->setAppends([]); #### hide appends attribute in select query ####
    }

}
