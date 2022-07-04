<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LawyerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'name'=>$this->user->name,
            'phone'=>$this->user->phone,
            'avatar' => $this->user->avater ? url($this->user->avatar)  : null,
            'instituteTel'=>$this->institute_tel,
            'licenseNumber'=>$this->license_number,
            'address'=>$this->address,
            'provinceArea'=>$this->province_area,
            'cityArea'=>$this->city_area,
            'expireDate'=>$this->expire_date,
            'grade'=>$this->grade,
            'postalCode'=>$this->postal_code,
            'expertises'=>$this->expertises,
            'isBookmarked'=>$this->is_bookmarked,

        ];
    }
}
