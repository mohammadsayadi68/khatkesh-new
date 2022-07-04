<?php

namespace App\Http\Controllers\Api;

use App\City;
use App\Http\Controllers\Controller;
use App\Province;

class PartnerController extends Controller
{
    public function provinces()
    {
        $provinces = \Cache::remember('provinces', 10000, function () {
            return Province::select('name','id')->get();
        });
        return $provinces;
    }

    public function cities($provinceId)
    {
        $provinces = \Cache::remember('cities-'.$provinceId, 10000, function () use($provinceId){
            return City::select('name','id','province_id as provinceId')->whereProvinceId($provinceId)->get();
        });
        return $provinces;
    }
}
