<?php

namespace App\Traits\Dashboard;

use App\Models\Brand;
use Freshbitsweb\Laratables\Laratables;

trait BrandsTrait
{
    use PublicTrait;
    public function getBrandById($id)
    {
        return Brand::find($id);
    }

    public function getAll()
    {
        return Laratables::recordsOf(Brand::class);
    }

    public function createBrand($request)
    {
        $fileName = "";
        if (isset($request->photo) && !empty($request->photo)) {
            $fileName = $this->uploadImage('brands', $request->photo);
        }
        $brand = Brand::create(['photo' => $fileName]);
        return $brand;
    }

    public function updateBrand($brand, $request)
    {
        $brand = $brand->update($request->all());
        return $brand;
    }

}
