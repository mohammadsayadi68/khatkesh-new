<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class LawyersCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {
            return [
                'id'=>$item->id,
                'name'=>$item->user->name,
//                'avatar' => $item->user->avater ? url($item->user->avatar)  : null,
                'provinceArea'=>$item->province_area,
                'cityArea'=>$item->city_area,
                'grade'=>$item->grade,
            ];
        });
    }
}
