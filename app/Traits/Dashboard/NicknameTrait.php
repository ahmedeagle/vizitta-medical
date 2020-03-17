<?php

namespace App\Traits\Dashboard;
use App\Models\Nickname;
use Freshbitsweb\Laratables\Laratables;

trait NicknameTrait
{
    public function getNicknameById($id){
        return Nickname::find($id);
    }

    public function getAllNicknames(){
        return Laratables::recordsOf(Nickname::class);
    }

    public function createNickname($request){
        $nickname = Nickname::create($request->all());
        return $nickname;
    }

    public function updateNickname($nickname, $request){
        $nickname = $nickname->update($request->all());
        return $nickname;
    }

}