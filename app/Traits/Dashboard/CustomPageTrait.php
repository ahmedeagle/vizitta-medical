<?php

namespace App\Traits\Dashboard;
use App\Models\CustomPage;
use Freshbitsweb\Laratables\Laratables;

trait CustomPageTrait
{
    public function getCustomPageById($id){
        return CustomPage::find($id);
    }

    public function getAll(){
        return Laratables::recordsOf(CustomPage::class);
    }

    public function createCustomPage($request){
        $customPage = CustomPage::create($request->all());
        return $customPage;
    }

    public function updateCustomPage($customPage, $request){
        $customPage = $customPage->update($request->all());
        return $customPage;
    }

    public function changerCustomPageStatus($customPage, $status){
        $customPage = $customPage->update([
            'status' => $status
        ]);
        return $customPage;
    }
}