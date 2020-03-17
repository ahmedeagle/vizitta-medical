<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\WinnersResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WinnersController extends Controller
{
    public function index()
    {
        $users = User::active()->whereHas('gifts')->paginate(PAGINATION_COUNT);
        $result['users'] = new WinnersResource($users);
        $result['userNotWinUntillNow'] = User::active()->whereDoesntHave('gifts')->count();
        return response()->json(['status' => true, 'data' => $result]);
    }

}
